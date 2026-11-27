<?php

namespace App\Support\Analytics;

/**
 * LegacyRules
 *
 * Turns a legacy form's visualization rules (forms.viz_rules: per question, whether it's charted, which SurveyJS
 * Analytics visualizer and chart type drew it, and for the "Grouped" visualizer the label question it was bound to)
 * into one Visualize tab of charts, in the form's order, for the legacy import:
 *
 *   selectBase / boolean / matrix: a question chart (doughnut and pie become a donut, stacked bars stay stacked)
 *   histogram / number (gauge):    a question chart, as a line where the question takes one, else its default
 *   bound ("Grouped"):             a grouped chart, the question (and its extra series) worked out per label answer
 *   word clouds, text tables, hidden questions and questions that can't be charted: left out
 *
 * Every chart's rules go through ChartRules, so nothing lands that the app wouldn't accept itself.
 */
class LegacyRules
{
    /** Legacy sort orders, as ours. */
    const SORTS = ['value_desc' => 'desc', 'value_asc' => 'asc', 'label' => 'label'];

    /** Legacy visualizers that only listed answers (there's no free-text chart). */
    const SKIPPED = ['wordcloud', 'text', 'choices'];

    /**
     * The tab the rules come to: its name and charts (kind, rules; titles are left to the app's own).
     *
     * @param  array<string, array<string, mixed>>|null  $rules  forms.viz_rules
     * @param  array<string, mixed>|null  $content  the form's SurveyJS definition
     * @return array{name: string, charts: list<array{kind: string, title: null, rules: array<string, mixed>}>}
     */
    public static function toTab(?array $rules, ?array $content): array
    {
        $questions = Questions::from($content);
        $charts = [];

        foreach ($questions as $question) {
            $rule = $rules[$question->name()] ?? null;
            if (! is_array($rule) || ($rule['visible'] ?? true) === false || in_array($rule['visualizer'] ?? null, self::SKIPPED, true)) {
                continue;
            }

            $chart = ($rule['visualizer'] ?? null) === 'bound'
                ? self::grouped($question, $rule, $questions)
                : self::question($question, $rule);
            if ($chart) {
                $charts[] = $chart;
            }
        }

        return ['name' => __('Charts'), 'charts' => $charts];
    }

    /**
     * A question chart from a legacy rule, or null when the question can't be charted.
     *
     * @param  array<string, mixed>  $rule
     * @return array{kind: string, title: null, rules: array<string, mixed>}|null
     */
    private static function question(Question $question, array $rule): ?array
    {
        $rules = ChartRules::clean([
            'question' => $question->name(),
            'visual' => self::visual($rule['chart_type'] ?? null, $question->visuals()),
            'sort' => 'form',
        ], [$question]);

        return $rules ? ['kind' => 'question', 'title' => null, 'rules' => $rules] : null;
    }

    /**
     * A grouped chart from a legacy "Grouped" rule: the question, and its extra series, worked out per answer of the
     * question it was bound to. Null when that doesn't make a chart the app takes.
     *
     * @param  array<string, mixed>  $rule
     * @param  list<Question>  $questions
     * @return array{kind: string, title: null, rules: array<string, mixed>}|null
     */
    private static function grouped(Question $question, array $rule, array $questions): ?array
    {
        $aggregate = in_array($rule['aggregate'] ?? null, ChartRules::AGGREGATES, true) ? $rule['aggregate'] : 'sum';
        $series = array_map(
            fn ($name) => ['question' => (string) $name, 'aggregate' => $aggregate],
            [$question->name(), ...(is_array($rule['series'] ?? null) ? $rule['series'] : [])],
        );

        $visual = match ($rule['chart_type'] ?? null) {
            'line' => 'line',
            'pie', 'doughnut' => count($series) === 1 ? 'donut' : 'bar',
            default => 'bar',
        };

        $rules = ChartRules::cleanGrouped([
            'label' => $rule['bind_label_to'] ?? null,
            'mode' => 'numbers',
            'series' => $series,
            'visual' => $visual,
            'sort' => self::SORTS[$rule['sort'] ?? ''] ?? 'desc',
        ], $questions);

        return $rules ? ['kind' => 'grouped', 'title' => null, 'rules' => $rules] : null;
    }

    /**
     * A legacy chart type as one of the question's visuals, or null for its default.
     *
     * @param  list<string>  $visuals  the visuals the question can take
     */
    private static function visual(?string $chartType, array $visuals): ?string
    {
        $wanted = match ($chartType) {
            'pie', 'doughnut' => 'donut',
            'bar' => 'bar',
            'stackedbar' => 'stacked',
            // vertical bars and histograms: a line where the question's steps run in order
            'vbar' => in_array('line', $visuals, true) ? 'line' : 'bar',
            default => null,
        };

        return in_array($wanted, $visuals, true) ? $wanted : null;
    }
}
