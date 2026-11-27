<?php

namespace App\Support;

/**
 * SurveyAnswers
 *
 * Reads a form's questions from its SurveyJS definition and turns stored answers into short readable text, for
 * responses lists: a choice's label in place of its value, lists joined, yes / no for booleans, a file's name. Each
 * question has a kind for how its cells read in the table: rating (4/5), number, file (a count) or text.
 */
class SurveyAnswers
{
    /** Element types that hold no answer. */
    const WITHOUT_ANSWERS = ['html', 'image', 'expression'];

    /**
     * The form's questions in order, panels and pages flattened.
     *
     * @return list<array{name: string, title: string, type: string, kind: string, max: int|null, choices: array<string, string>}>
     */
    public static function questions(?array $content): array
    {
        $questions = [];
        foreach ($content['pages'] ?? [] as $page) {
            self::collect($page['elements'] ?? [], $questions);
        }

        return $questions;
    }

    /**
     * An answer as one line of text ('' when there's none).
     *
     * @param  array{type: string, choices: array<string, string>}|null  $question
     */
    public static function format(mixed $value, ?array $question = null): string
    {
        if ($value === null || $value === '' || $value === []) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? __('Yes') : __('No');
        }

        if (is_array($value)) {
            // a file upload: [{name, type, content}, ...]
            if (array_is_list($value) && isset($value[0]['name']) && is_array($value[0])) {
                return implode(', ', array_column($value, 'name'));
            }
            if (array_is_list($value)) {
                return implode(', ', array_map(fn ($item) => self::format($item, $question), $value));
            }

            // a matrix or multiple text: row: answer
            return implode('; ', array_map(fn ($key, $item) => $key.': '.self::format($item, $question), array_keys($value), $value));
        }

        // a drawing (signature pad) or a file kept as a data URL: never the data itself
        if (is_string($value) && str_starts_with($value, 'data:')) {
            return ($question['type'] ?? null) === 'signaturepad' ? __('Signature') : trans_choice(':count file|:count files', 1);
        }

        $text = (string) $value;

        return $question['choices'][$text] ?? $text;
    }

    /**
     * An answer as a table cell: a rating out of its maximum (4/5), a count of files, otherwise format().
     *
     * @param  array{type: string, kind: string, max: int|null, choices: array<string, string>}  $question
     */
    public static function cell(mixed $value, array $question): string
    {
        if ($value === null || $value === '' || $value === []) {
            return '';
        }

        return match ($question['kind']) {
            'rating' => is_scalar($value) && $question['max'] ? $value.'/'.$question['max'] : self::format($value, $question),
            'file' => is_array($value) ? trans_choice(':count file|:count files', count($value)) : self::format($value, $question),
            default => self::format($value, $question),
        };
    }

    /**
     * Adds the elements' questions to the list, going into panels.
     *
     * @param  list<array{name: string, title: string, type: string, kind: string, max: int|null, choices: array<string, string>}>  $questions
     */
    private static function collect(array $elements, array &$questions): void
    {
        foreach ($elements as $element) {
            $type = $element['type'] ?? '';

            // a panel's questions answer on their own; a dynamic panel is one question (a list of entries)
            if ($type === 'panel') {
                self::collect($element['elements'] ?? [], $questions);

                continue;
            }
            if (empty($element['name']) || in_array($type, self::WITHOUT_ANSWERS, true)) {
                continue;
            }

            $questions[] = [
                'name' => $element['name'],
                'title' => self::text($element['title'] ?? null) ?: $element['name'],
                'type' => $type,
                'kind' => self::kind($element),
                'max' => $type === 'rating' ? self::ratingMax($element) : null,
                'choices' => self::choices($element['rateValues'] ?? $element['choices'] ?? []),
            ];
        }
    }

    /** How the question's answers read in a table: rating, number, file (uploads and signatures) or text. */
    private static function kind(array $element): string
    {
        return match (true) {
            ($element['type'] ?? '') === 'rating' => 'rating',
            in_array($element['type'] ?? '', ['file', 'signaturepad'], true) => 'file',
            ($element['type'] ?? '') === 'text' && in_array($element['inputType'] ?? '', ['number', 'range'], true) => 'number',
            default => 'text',
        };
    }

    /** A rating's top value: its last listed value, or rateMax / rateCount (SurveyJS's default is 5). */
    private static function ratingMax(array $element): int
    {
        if (! empty($element['rateValues'])) {
            $last = end($element['rateValues']);
            $value = is_array($last) ? ($last['value'] ?? null) : $last;

            return is_numeric($value) ? (int) $value : count($element['rateValues']);
        }

        return (int) ($element['rateMax'] ?? $element['rateCount'] ?? 5);
    }

    /**
     * A question's choices as value => label.
     *
     * @return array<string, string>
     */
    public static function choices(array $choices): array
    {
        $labels = [];
        foreach ($choices as $choice) {
            if (is_array($choice)) {
                $value = (string) ($choice['value'] ?? '');
                $labels[$value] = self::text($choice['text'] ?? null) ?: $value;
            } else {
                $labels[(string) $choice] = (string) $choice;
            }
        }

        return $labels;
    }

    /** A SurveyJS text that may be translated ({default: ..., fr: ...}): the default language's. */
    public static function text(mixed $text): string
    {
        if (is_array($text)) {
            $text = $text['default'] ?? reset($text);
        }

        return is_string($text) ? trim(strip_tags($text)) : '';
    }
}
