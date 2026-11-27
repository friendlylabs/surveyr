<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * SubmissionFilters
 *
 * Filters on a form's responses (the responses table's filter panel and saved views), as legacy's submission
 * filters: a date range on when responses came in, and rules on answers.
 *
 *   ['from' => '2026-11-01', 'to' => '2026-11-30', 'rules' => [['question' => 'village', 'operator' => 'contains', 'value' => 'kon']]]
 *
 * Rules on different questions must all match; rules on the same question match when any of them does. What a rule
 * can say depends on its question: text (contains, is, ...), number (greater than, between, ...), choice (is any of,
 * is none of) or file (only whether there is one); every kind can ask for an empty or a filled answer.
 */
class SubmissionFilters
{
    /** Most rules one filter keeps. */
    const MAX_RULES = 20;

    /** Operators by filter kind. */
    const OPERATORS = [
        'text' => ['contains', 'notContains', 'is', 'empty', 'notEmpty'],
        'number' => ['eq', 'neq', 'gt', 'gte', 'lt', 'lte', 'between', 'empty', 'notEmpty'],
        'choice' => ['in', 'notIn', 'empty', 'notEmpty'],
        'file' => ['notEmpty', 'empty'],
    ];

    /** Question types answered with one or more of their choices. */
    const CHOICE_TYPES = ['radiogroup', 'dropdown', 'checkbox', 'tagbox', 'imagepicker', 'boolean'];

    /**
     * Operator labels, for the filter panel and the descriptions.
     *
     * @return array<string, string>
     */
    public static function operatorLabels(): array
    {
        return [
            'contains' => __('contains'), 'notContains' => __('doesn\'t contain'), 'is' => __('is'),
            'eq' => __('is'), 'neq' => __('isn\'t'), 'gt' => __('is more than'), 'gte' => __('is at least'),
            'lt' => __('is less than'), 'lte' => __('is at most'), 'between' => __('is between'),
            'in' => __('is any of'), 'notIn' => __('is none of'), 'empty' => __('is empty'), 'notEmpty' => __('is answered'),
        ];
    }

    /**
     * The questions a filter can use, with their kind and choices, keyed by name (the panel's config).
     *
     * @param  list<array{name: string, title: string, type: string, kind: string, choices: array<string, string>}>  $questions
     * @return array<string, array{title: string, kind: string, choices: array<string, string>}>
     */
    public static function fields(array $questions): array
    {
        $fields = [];
        foreach ($questions as $question) {
            // the name goes into a JSON path, so only plain names are filterable
            if (preg_match('/^[\pL\pN _.-]+$/u', $question['name']) !== 1) {
                continue;
            }
            $kind = match (true) {
                $question['kind'] === 'file' => 'file',
                in_array($question['kind'], ['number', 'rating'], true) => 'number',
                in_array($question['type'], self::CHOICE_TYPES, true) => 'choice',
                default => 'text',
            };
            $choices = $question['type'] === 'boolean' ? ['true' => __('Yes'), 'false' => __('No')] : $question['choices'];
            $fields[$question['name']] = ['title' => $question['title'], 'kind' => $kind, 'choices' => $kind === 'choice' ? $choices : []];
        }

        return $fields;
    }

    /**
     * Cleans filters from a request or a saved view against the form's questions: unknown questions, operators that
     * don't fit them and empty values are dropped.
     *
     * @param  array<string, array{title: string, kind: string, choices: array<string, string>}>  $fields
     * @return array{from: ?string, to: ?string, rules: list<array{question: string, operator: string, value: mixed}>}
     */
    public static function normalise(mixed $input, array $fields): array
    {
        $input = is_array($input) ? $input : [];
        $rules = [];

        foreach (array_slice(is_array($input['rules'] ?? null) ? $input['rules'] : [], 0, self::MAX_RULES) as $rule) {
            $field = is_array($rule) ? ($fields[$rule['question'] ?? ''] ?? null) : null;
            $operator = is_array($rule) ? ($rule['operator'] ?? null) : null;
            if (! $field || ! in_array($operator, self::OPERATORS[$field['kind']], true)) {
                continue;
            }
            $value = self::value($operator, $rule['value'] ?? null, $field);
            if ($value === false) {
                continue;
            }
            $rules[] = ['question' => $rule['question'], 'operator' => $operator, 'value' => $value];
        }

        return ['from' => self::date($input['from'] ?? null), 'to' => self::date($input['to'] ?? null), 'rules' => $rules];
    }

    /** How many constraints the filters carry (a date range counts once). */
    public static function count(array $filters): int
    {
        return count($filters['rules'] ?? []) + (! empty($filters['from']) || ! empty($filters['to']) ? 1 : 0);
    }

    /**
     * Narrows a responses query to the (normalised) filters.
     *
     * @param  array{from: ?string, to: ?string, rules: list<array{question: string, operator: string, value: mixed}>}  $filters
     */
    public static function apply(Builder $query, array $filters): void
    {
        if (! empty($filters['from'])) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }
        if (! empty($filters['to'])) {
            $query->whereDate('created_at', '<=', $filters['to']);
        }

        // rules on one question are alternatives; questions must all match
        foreach (collect($filters['rules'] ?? [])->groupBy('question') as $question => $rules) {
            $query->where(function (Builder $group) use ($question, $rules) {
                foreach ($rules as $rule) {
                    $group->orWhere(fn (Builder $one) => self::rule($one, 'data->'.$question, $rule['operator'], $rule['value']));
                }
            });
        }
    }

    /**
     * The filters in words, one line per constraint (the saved views list).
     *
     * @param  array<string, array{title: string, kind: string, choices: array<string, string>}>  $fields
     * @return list<string>
     */
    public static function describe(array $filters, array $fields): array
    {
        $labels = self::operatorLabels();
        $lines = [];

        if (! empty($filters['from']) || ! empty($filters['to'])) {
            $lines[] = match (true) {
                empty($filters['to']) => __('Since :date', ['date' => Carbon::parse($filters['from'])->format('j M Y')]),
                empty($filters['from']) => __('Until :date', ['date' => Carbon::parse($filters['to'])->format('j M Y')]),
                default => Carbon::parse($filters['from'])->format('j M Y').' – '.Carbon::parse($filters['to'])->format('j M Y'),
            };
        }

        foreach ($filters['rules'] ?? [] as $rule) {
            $field = $fields[$rule['question']] ?? null;
            if (! $field) {
                continue;
            }
            $value = match (true) {
                $rule['value'] === null => '',
                $rule['operator'] === 'between' => implode(' – ', array_filter([$rule['value']['min'] ?? null, $rule['value']['max'] ?? null], fn ($part) => $part !== null)),
                is_array($rule['value']) => implode(', ', array_map(fn ($choice) => $field['choices'][$choice] ?? $choice, $rule['value'])),
                default => '“'.$rule['value'].'”',
            };
            $lines[] = trim($field['title'].' '.$labels[$rule['operator']].' '.$value);
        }

        return $lines;
    }

    /** One rule on one JSON path. */
    private static function rule(Builder $query, string $path, string $operator, mixed $value): void
    {
        $column = $query->getQuery()->getGrammar()->wrap($path);

        match ($operator) {
            'contains' => $query->whereRaw("lower($column) like ? escape '!'", ['%'.self::like($value).'%']),
            'notContains' => $query->where(fn (Builder $q) => $q->whereNull($path)->orWhereRaw("lower($column) not like ? escape '!'", ['%'.self::like($value).'%'])),
            'is' => $query->whereRaw("lower($column) = ?", [mb_strtolower($value)]),
            'eq', 'neq', 'gt', 'gte', 'lt', 'lte' => $query->whereRaw(
                "cast($column as decimal(20, 6)) ".['eq' => '=', 'neq' => '<>', 'gt' => '>', 'gte' => '>=', 'lt' => '<', 'lte' => '<='][$operator].' ?', [$value]
            ),
            'between' => $query
                ->when($value['min'] !== null, fn (Builder $q) => $q->whereRaw("cast($column as decimal(20, 6)) >= ?", [$value['min']]))
                ->when($value['max'] !== null, fn (Builder $q) => $q->whereRaw("cast($column as decimal(20, 6)) <= ?", [$value['max']])),
            'in' => $query->where(fn (Builder $q) => self::anyOf($q, $path, $value)),
            'notIn' => $query->where(fn (Builder $q) => $q->whereNull($path)->orWhereNot(fn (Builder $not) => self::anyOf($not, $path, $value))),
            'empty' => $query->where(fn (Builder $q) => $q->whereNull($path)->orWhere($path, '')->orWhere($path, '[]')),
            'notEmpty' => $query->whereNotNull($path)->where($path, '<>', '')->where($path, '<>', '[]'),
        };
    }

    /** The answer is (or, for a list of choices, includes) any of the values; yes / no answers are JSON booleans. */
    private static function anyOf(Builder $query, string $path, array $values): void
    {
        foreach ($values as $value) {
            if ($value === 'true' || $value === 'false') {
                $query->orWhere($path, $value === 'true');
            } else {
                $query->orWhereJsonContains($path, $value);
            }
        }
    }

    /**
     * A rule's value cleaned for its operator, or false when it has none worth filtering on.
     *
     * @param  array{kind: string, choices: array<string, string>}  $field
     */
    private static function value(string $operator, mixed $value, array $field): mixed
    {
        switch ($operator) {
            case 'empty':
            case 'notEmpty':
                return null;
            case 'between':
                $min = is_array($value) && is_numeric($value['min'] ?? null) ? $value['min'] + 0 : null;
                $max = is_array($value) && is_numeric($value['max'] ?? null) ? $value['max'] + 0 : null;

                return $min === null && $max === null ? false : ['min' => $min, 'max' => $max];
            case 'in':
            case 'notIn':
                $chosen = array_values(array_intersect(array_map('strval', is_array($value) ? $value : [$value]), array_map('strval', array_keys($field['choices']))));

                return $chosen ?: false;
            case 'eq':
            case 'neq':
            case 'gt':
            case 'gte':
            case 'lt':
            case 'lte':
                return is_numeric($value) ? $value + 0 : false;
            default:
                $text = is_scalar($value) ? mb_substr(trim((string) $value), 0, 200) : '';

                return $text === '' ? false : $text;
        }
    }

    /** A Y-m-d date, or null. */
    private static function date(mixed $value): ?string
    {
        return is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) && strtotime($value) ? $value : null;
    }

    /** Text for a LIKE pattern: lower case, its wildcards escaped with "!" (an escape both MySQL and SQLite take). */
    private static function like(string $value): string
    {
        return str_replace(['!', '%', '_'], ['!!', '!%', '!_'], mb_strtolower($value));
    }
}
