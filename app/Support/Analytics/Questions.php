<?php

namespace App\Support\Analytics;

/**
 * Questions
 *
 * Reads a form's questions, in order, from its SurveyJS definition for the visualizer: pages (or a top-level elements
 * list) and panels flattened; a dynamic panel is one question, as its answers are a list of entries.
 */
class Questions
{
    /** Element types that hold no answer. */
    const WITHOUT_ANSWERS = ['html', 'image'];

    /**
     * The form's questions in order.
     *
     * @return list<Question>
     */
    public static function from(?array $content): array
    {
        $questions = [];
        self::collect($content['elements'] ?? [], $questions);
        foreach ($content['pages'] ?? [] as $page) {
            self::collect(is_array($page) ? ($page['elements'] ?? []) : [], $questions);
        }

        return $questions;
    }

    /**
     * Adds the elements' questions to the list, going into panels.
     *
     * @param  list<Question>  $questions
     */
    private static function collect(mixed $elements, array &$questions): void
    {
        foreach (is_array($elements) ? $elements : [] as $element) {
            if (! is_array($element)) {
                continue;
            }
            $type = $element['type'] ?? '';

            if ($type === 'panel') {
                self::collect($element['elements'] ?? [], $questions);

                continue;
            }
            if (! is_string($element['name'] ?? null) || $element['name'] === '' || in_array($type, self::WITHOUT_ANSWERS, true)) {
                continue;
            }

            $questions[] = new Question($element);
        }
    }
}
