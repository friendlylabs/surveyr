<?php

namespace App\Support\Analytics\Summaries;

/**
 * ChoiceSummary
 *
 * A choice question (radiogroup, dropdown, imagepicker; checkbox and tagbox pick several): how many picked each choice,
 * as a share of those who answered. Values the form doesn't list are counted under their own value (choices from a web
 * service), or as "Other" when the question has an Other box and keeps its text in the answer. Past MAX_CATEGORIES
 * the rarest such values are lumped together. Typed Other texts ({key}-Comment) are counted for the answers list.
 */
class ChoiceSummary extends Summary
{
    /** Most rows a chart gets; rarer unlisted values share the last. */
    const MAX_CATEGORIES = 25;

    /** @var array<string, int> picks per value */
    private array $counts = [];

    /** Answers with an Other text. */
    private int $otherTexts = 0;

    /** {@inheritDoc} */
    protected function collect(mixed $value, array $data, array $response): bool
    {
        $values = $this->question->multiple() ? (is_array($value) && array_is_list($value) ? $value : null) : (is_scalar($value) ? [$value] : null);
        $values = array_values(array_unique(array_map(fn ($item) => is_bool($item) ? ($item ? 'true' : 'false') : (string) $item, array_filter($values ?? [], 'is_scalar'))));
        if (! $values) {
            return false;
        }

        foreach ($values as $item) {
            $this->counts[$item] = ($this->counts[$item] ?? 0) + 1;
        }
        $comment = $data[$this->question->key().'-Comment'] ?? null;
        if (in_array('other', $values, true) && is_string($comment) && trim($comment) !== '') {
            $this->otherTexts++;
        }

        return true;
    }

    /** {@inheritDoc} */
    protected function details(): array
    {
        $counts = $this->counts;
        $rows = [];
        foreach ($this->question->choices() as $value => $label) {
            $rows[] = $this->row((string) $value, $label, $counts[$value] ?? 0);
            unset($counts[$value]);
        }

        $other = $counts['other'] ?? 0;
        $none = $counts['none'] ?? 0;
        unset($counts['other'], $counts['none']);

        // values the form doesn't list: the Other box's own text, or choices only the answers know
        if ($this->question->hasOther() && ! $this->question->choicesFromUrl()) {
            $other += array_sum($counts);
            $this->otherTexts += array_sum($counts);
        } else {
            arsort($counts);
            $room = max(1, self::MAX_CATEGORIES - count($rows));
            $kept = count($counts) > $room ? array_slice($counts, 0, $room - 1, true) : $counts;
            foreach ($kept as $value => $count) {
                $rows[] = $this->row((string) $value, (string) $value, $count);
            }
            if (count($counts) > count($kept)) {
                $rows[] = $this->row('', __('Other values'), array_sum(array_slice($counts, count($kept), null, true)), 'rest');
            }
        }

        if ($this->question->hasOther() || $other) {
            $rows[] = $this->row('other', $this->question->label('otherText') ?? __('Other'), $other, 'other');
        }
        if ($this->question->hasNone() || $none) {
            $rows[] = $this->row('none', $this->question->label('noneText') ?? __('None'), $none, 'none');
        }

        return ['multiple' => $this->question->multiple(), 'categories' => $rows, 'otherAnswers' => $this->otherTexts];
    }

    /**
     * One row: a choice, how many picked it and their share of the answers.
     *
     * @return array{value: string, label: string, count: int, percent: float, special: ?string}
     */
    private function row(string $value, string $label, int $count, ?string $special = null): array
    {
        return ['value' => $value, 'label' => $label, 'count' => $count, 'percent' => self::percent($count, $this->answered), 'special' => $special];
    }
}
