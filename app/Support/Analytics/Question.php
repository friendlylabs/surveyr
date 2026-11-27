<?php

namespace App\Support\Analytics;

use App\Support\SurveyAnswers;

/**
 * Question
 *
 * One question of a form as the visualizer sees it: where its answer sits in a response (valueName, else name), its
 * title and choices, and the kind of summary it gets (choice, boolean, rating, nps, number, ranking, matrix, text,
 * file, signature or unsupported) with the visuals that summary can be shown as, default first.
 */
class Question
{
    /**
     * Visuals each kind can be shown as; the first is the default. A line only joins steps that run in order (rating
     * steps, number ranges), never unordered choices.
     */
    const VISUALS = [
        'choice' => ['bar', 'donut', 'table'],
        'choices' => ['bar', 'table'],
        'boolean' => ['donut', 'bar', 'table'],
        'rating' => ['line', 'bar', 'table'],
        'nps' => ['nps', 'line', 'table'],
        'number' => ['line', 'table'],
        'ranking' => ['bar', 'table'],
        'matrix' => ['heat', 'stacked'],
        'text' => ['list'],
        'file' => ['files'],
        'signature' => ['stats'],
        'unsupported' => ['stats'],
    ];

    /** The summary kind: VISUALS' keys. */
    public readonly string $kind;

    /**
     * @param  array<string, mixed>  $element  the question's SurveyJS definition
     */
    public function __construct(public readonly array $element)
    {
        $this->kind = $this->resolveKind();
    }

    /** The key its answer is stored under. */
    public function key(): string
    {
        return (string) ($this->element['valueName'] ?? $this->element['name']);
    }

    /** The question's name in the form. */
    public function name(): string
    {
        return (string) $this->element['name'];
    }

    /** Its type (radiogroup, rating, ...). */
    public function type(): string
    {
        return (string) ($this->element['type'] ?? '');
    }

    /** Its title in the form's default language, else its name. */
    public function title(): string
    {
        return SurveyAnswers::text($this->element['title'] ?? null) ?: $this->name();
    }

    /**
     * Its choices as value => label (a rating's steps, for ratings).
     *
     * @return array<string, string>
     */
    public function choices(): array
    {
        if ($this->type() === 'rating') {
            return $this->ratingSteps();
        }

        return SurveyAnswers::choices(is_array($this->element['choices'] ?? null) ? $this->element['choices'] : []);
    }

    /**
     * A matrix's rows or columns as value => label.
     *
     * @return array<string, string>
     */
    public function matrix(string $part): array
    {
        return SurveyAnswers::choices(is_array($this->element[$part] ?? null) ? $this->element[$part] : []);
    }

    /** Whether more than one choice can be picked. */
    public function multiple(): bool
    {
        return in_array($this->type(), ['checkbox', 'tagbox'], true) || ($this->type() === 'imagepicker' && ! empty($this->element['multiSelect']));
    }

    /** Whether it lists an "Other" choice with its own text box. */
    public function hasOther(): bool
    {
        return ! empty($this->element['showOtherItem']) || ! empty($this->element['hasOther']);
    }

    /** Whether it lists a "None" choice. */
    public function hasNone(): bool
    {
        return ! empty($this->element['showNoneItem']) || ! empty($this->element['hasNone']);
    }

    /**
     * The Other texts in a response: the comment box's ({key}-Comment), or, where the form keeps the text as the answer
     * itself, the picked values it doesn't list.
     *
     * @param  array<string, mixed>  $data
     * @return list<string>
     */
    public function otherTexts(array $data): array
    {
        $texts = [];
        $comment = $data[$this->key().'-Comment'] ?? null;
        if (is_string($comment) && trim($comment) !== '') {
            $texts[] = trim($comment);
        }

        if ($this->hasOther() && ! $this->choicesFromUrl()) {
            $value = $data[$this->key()] ?? null;
            $listed = array_map('strval', array_keys($this->choices()));
            foreach (is_array($value) ? $value : [$value] as $item) {
                if (is_scalar($item) && ! is_bool($item) && trim((string) $item) !== ''
                    && ! in_array((string) $item, [...$listed, 'other', 'none'], true)) {
                    $texts[] = trim((string) $item);
                }
            }
        }

        return $texts;
    }

    /** Whether its choices come from a web service, so they're only known from the answers. */
    public function choicesFromUrl(): bool
    {
        return ! empty($this->element['choicesByUrl']['url']);
    }

    /** The form's own label for a special item (otherText, noneText, labelTrue, ...), or null for the app's. */
    public function label(string $property): ?string
    {
        return SurveyAnswers::text($this->element[$property] ?? null) ?: null;
    }

    /**
     * The visuals it can be shown as, default first.
     *
     * @return list<string>
     */
    public function visuals(): array
    {
        return self::VISUALS[$this->kind];
    }

    /**
     * A rating's steps as value => label: its listed values, else rateMin to rateMax (or rateCount steps) by rateStep.
     *
     * @return array<string, string>
     */
    public function ratingSteps(): array
    {
        if (! empty($this->element['rateValues']) && is_array($this->element['rateValues'])) {
            return SurveyAnswers::choices($this->element['rateValues']);
        }

        $min = is_numeric($this->element['rateMin'] ?? null) ? $this->element['rateMin'] + 0 : 1;
        $step = is_numeric($this->element['rateStep'] ?? null) && $this->element['rateStep'] > 0 ? $this->element['rateStep'] + 0 : 1;
        $max = match (true) {
            is_numeric($this->element['rateMax'] ?? null) => $this->element['rateMax'] + 0,
            is_numeric($this->element['rateCount'] ?? null) => $min + ((int) $this->element['rateCount'] - 1) * $step,
            default => 5,
        };

        $steps = [];
        // at most 100 steps, whatever the definition says
        for ($value = $min, $i = 0; $value <= $max && $i < 100; $value += $step, $i++) {
            $steps[(string) $value] = (string) $value;
        }

        return $steps;
    }

    /** The summary kind for its type. */
    private function resolveKind(): string
    {
        return match ($this->type()) {
            'radiogroup', 'dropdown', 'imagepicker', 'checkbox', 'tagbox' => $this->multiple() ? 'choices' : 'choice',
            'boolean' => 'boolean',
            // array keys of numeric strings are ints, so compare as text
            'rating' => array_map('strval', array_keys($this->ratingSteps())) === array_map('strval', range(0, 10)) ? 'nps' : 'rating',
            'ranking' => 'ranking',
            'matrix' => 'matrix',
            'file' => 'file',
            'signaturepad' => 'signature',
            'expression' => 'number',
            'text' => in_array($this->element['inputType'] ?? '', ['number', 'range'], true) ? 'number' : 'text',
            'comment' => 'text',
            default => 'unsupported',
        };
    }
}
