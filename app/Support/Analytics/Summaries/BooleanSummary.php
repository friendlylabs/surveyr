<?php

namespace App\Support\Analytics\Summaries;

/**
 * BooleanSummary
 *
 * A yes / no question: how many said each, by the question's own values (valueTrue / valueFalse) when it has them,
 * labelled with its labelTrue / labelFalse, else Yes / No.
 */
class BooleanSummary extends Summary
{
    /** Answers that were yes. */
    private int $yes = 0;

    /** Answers that were no. */
    private int $no = 0;

    /** {@inheritDoc} */
    protected function collect(mixed $value, array $data, array $response): bool
    {
        $element = $this->question->element;
        $answer = match (true) {
            array_key_exists('valueTrue', $element) && $value === $element['valueTrue'] => true,
            array_key_exists('valueFalse', $element) && $value === $element['valueFalse'] => false,
            $value === true || $value === 'true' => true,
            $value === false || $value === 'false' => false,
            default => null,
        };
        if ($answer === null) {
            return false;
        }

        $answer ? $this->yes++ : $this->no++;

        return true;
    }

    /** {@inheritDoc} */
    protected function details(): array
    {
        return ['categories' => [
            ['value' => 'true', 'label' => $this->question->label('labelTrue') ?? __('Yes'), 'count' => $this->yes, 'percent' => self::percent($this->yes, $this->answered), 'special' => null],
            ['value' => 'false', 'label' => $this->question->label('labelFalse') ?? __('No'), 'count' => $this->no, 'percent' => self::percent($this->no, $this->answered), 'special' => null],
        ]];
    }
}
