<?php

namespace Tests\Feature;

use App\Support\Analytics\LegacyRules;
use Tests\TestCase;

/**
 * LegacyRulesTest
 *
 * Legacy visualization rules (forms.viz_rules) as a Visualize tab (App\Support\Analytics\LegacyRules), on the legacy
 * demo form's own definition and rules: which charts come over, as what, and what's left out.
 */
class LegacyRulesTest extends TestCase
{
    /** The legacy demo form (School ICT Inventory), as stored. */
    private const CONTENT = ['pages' => [
        ['name' => 'site', 'elements' => [
            ['name' => 'school_name', 'type' => 'text', 'title' => 'School Name'],
            ['name' => 'region', 'type' => 'dropdown', 'title' => 'Region', 'choices' => [['text' => 'Dar es Salaam', 'value' => 'dar'], ['text' => 'Pwani', 'value' => 'pwani']]],
            ['name' => 'school_type', 'type' => 'radiogroup', 'title' => 'School Type', 'choices' => [['text' => 'Primary', 'value' => 'primary']]],
            ['name' => 'visit_date', 'type' => 'text', 'title' => 'Visit Date', 'inputType' => 'date'],
            ['name' => 'contact_email', 'type' => 'text', 'title' => 'Contact Email', 'inputType' => 'email'],
        ]],
        ['name' => 'inventory', 'elements' => [
            ['name' => 'computers', 'type' => 'text', 'title' => 'Number of Computers', 'inputType' => 'number'],
            ['name' => 'monitors', 'type' => 'text', 'title' => 'Number of Monitors', 'inputType' => 'number'],
            ['name' => 'students_enrolled', 'type' => 'text', 'title' => 'Students Enrolled', 'inputType' => 'number'],
            ['name' => 'condition_rating', 'type' => 'rating', 'title' => 'Equipment Condition', 'rateMax' => 5, 'rateMin' => 1],
            ['name' => 'has_internet', 'type' => 'boolean', 'title' => 'Has Internet Access'],
            ['name' => 'power_source', 'type' => 'checkbox', 'title' => 'Power Source', 'choices' => [['text' => 'Solar', 'value' => 'solar']]],
            ['name' => 'technician_notes', 'type' => 'comment', 'title' => 'Technician Notes'],
        ]],
    ]];

    /** Its rules, as stored (abridged to the questions above). */
    private const RULES = [
        'region' => ['sort' => 'value_desc', 'series' => [], 'visible' => true, 'aggregate' => 'sum', 'chart_type' => 'bar', 'visualizer' => 'selectBase', 'bind_label_to' => null],
        'monitors' => ['sort' => 'label', 'series' => [], 'visible' => true, 'aggregate' => 'sum', 'chart_type' => 'vbar', 'visualizer' => 'bound', 'bind_label_to' => 'school_name'],
        'computers' => ['sort' => 'label', 'series' => ['monitors'], 'visible' => true, 'aggregate' => 'avg', 'chart_type' => 'line', 'visualizer' => 'bound', 'bind_label_to' => 'school_name'],
        'visit_date' => ['sort' => 'value_desc', 'series' => [], 'visible' => true, 'aggregate' => 'sum', 'chart_type' => 'vbar', 'visualizer' => 'histogram', 'bind_label_to' => null],
        'school_name' => ['sort' => 'value_desc', 'series' => [], 'visible' => false, 'aggregate' => 'sum', 'chart_type' => null, 'visualizer' => 'wordcloud', 'bind_label_to' => null],
        'school_type' => ['sort' => 'value_desc', 'series' => [], 'visible' => true, 'aggregate' => 'sum', 'chart_type' => 'doughnut', 'visualizer' => 'selectBase', 'bind_label_to' => null],
        'has_internet' => ['sort' => 'value_desc', 'series' => [], 'visible' => true, 'aggregate' => 'sum', 'chart_type' => 'bar', 'visualizer' => 'boolean', 'bind_label_to' => null],
        'power_source' => ['sort' => 'value_desc', 'series' => [], 'visible' => false, 'aggregate' => 'sum', 'chart_type' => 'bar', 'visualizer' => 'selectBase', 'bind_label_to' => null],
        'contact_email' => ['sort' => 'value_desc', 'series' => [], 'visible' => true, 'aggregate' => 'sum', 'chart_type' => null, 'visualizer' => null, 'bind_label_to' => null],
        'condition_rating' => ['sort' => 'value_desc', 'series' => [], 'visible' => true, 'aggregate' => 'sum', 'chart_type' => 'vbar', 'visualizer' => 'histogram', 'bind_label_to' => null],
        'technician_notes' => ['sort' => 'value_desc', 'series' => [], 'visible' => true, 'aggregate' => 'sum', 'chart_type' => null, 'visualizer' => 'wordcloud', 'bind_label_to' => null],
        'students_enrolled' => ['sort' => 'label', 'series' => [], 'visible' => true, 'aggregate' => 'sum', 'chart_type' => 'vbar', 'visualizer' => 'bound', 'bind_label_to' => 'missing_question'],
    ];

    public function test_the_charts_come_over_in_the_forms_order_as_the_apps_own(): void
    {
        $tab = LegacyRules::toTab(self::RULES, self::CONTENT);

        $this->assertSame('Charts', $tab['name']);
        $this->assertSame([
            ['kind' => 'question', 'title' => null, 'rules' => ['question' => 'region', 'visual' => 'bar', 'sort' => 'form', 'limit' => 0]],
            ['kind' => 'question', 'title' => null, 'rules' => ['question' => 'school_type', 'visual' => 'donut', 'sort' => 'form', 'limit' => 0]],
            ['kind' => 'grouped', 'title' => null, 'rules' => [
                'label' => 'school_name', 'mode' => 'numbers',
                'series' => [['question' => 'computers', 'aggregate' => 'avg'], ['question' => 'monitors', 'aggregate' => 'avg']],
                'split' => null, 'visual' => 'line', 'sort' => 'label', 'limit' => 0,
            ]],
            ['kind' => 'grouped', 'title' => null, 'rules' => [
                'label' => 'school_name', 'mode' => 'numbers', 'series' => [['question' => 'monitors', 'aggregate' => 'sum']],
                'split' => null, 'visual' => 'bar', 'sort' => 'label', 'limit' => 0,
            ]],
            // a histogram of a rating: a line, as its steps run in order
            ['kind' => 'question', 'title' => null, 'rules' => ['question' => 'condition_rating', 'visual' => 'line', 'sort' => 'form', 'limit' => 0]],
            ['kind' => 'question', 'title' => null, 'rules' => ['question' => 'has_internet', 'visual' => 'bar', 'sort' => 'form', 'limit' => 0]],
        ], $tab['charts']);
    }

    public function test_what_cant_be_charted_is_left_out(): void
    {
        $charted = array_map(fn (array $chart) => $chart['rules']['question'] ?? $chart['rules']['series'][0]['question'], LegacyRules::toTab(self::RULES, self::CONTENT)['charts']);

        // word clouds, hidden questions, free text (a date, an email) and a grouping by a question that's gone
        foreach (['school_name', 'technician_notes', 'power_source', 'visit_date', 'contact_email', 'students_enrolled'] as $question) {
            $this->assertNotContains($question, $charted);
        }
    }

    public function test_a_form_without_rules_gives_an_empty_tab(): void
    {
        $this->assertSame([], LegacyRules::toTab(null, self::CONTENT)['charts']);
    }
}
