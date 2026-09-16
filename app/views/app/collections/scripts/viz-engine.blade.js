/**
 * Visualization engine
 * --------------------------------------------------------------------------
 * Wraps SurveyJS Analytics so a form decides *what* gets charted and *how*,
 * instead of dropping one generic chart per question on the page.
 *
 * It adds two things on top of the stock VisualizationPanel:
 *
 *   1. Rules      a per question record persisted on `forms.viz_rules`, saying
 *                 whether the question is charted, which visualizer renders it
 *                 and which chart type that visualizer uses.
 *
 *   2. Binding    a custom "Grouped" visualizer that plots a numeric question
 *                 against a label question, so "Number of Computers" becomes
 *                 computers *per school* rather than a meaningless average of
 *                 every submission.
 *
 * Both the rules editor (visualization-rules.blade.js) and the page bootstrap
 * (visualize.blade.js) read their vocabulary from here.
 */
window.VizEngine = (function (global) {
    'use strict';

    const SA = global.SurveyAnalytics;

    /* ---------------------------------------------------------------------- *
     |  Catalogue                                                             |
     * ---------------------------------------------------------------------- */

    const BOUND = 'bound';

    /**
     * Visualizer key -> display label and the chart types its plotly adapter
     * accepts. Anything not listed here still renders, it just doesn't offer a
     * chart type choice in the editor.
     */
    const VISUALIZERS = {
        number:     { label: 'Average',    charts: ['gauge', 'bullet'] },
        histogram:  { label: 'Histogram',  charts: ['vbar', 'bar'] },
        selectBase: { label: 'Chart',      charts: ['bar', 'vbar', 'pie', 'doughnut'] },
        choices:    { label: 'Table',      charts: [] },
        wordcloud:  { label: 'Word cloud', charts: [] },
        text:       { label: 'Table',      charts: [] },
        boolean:    { label: 'Chart',      charts: ['pie', 'bar', 'doughnut'] },
        matrix:     { label: 'Chart',      charts: ['bar', 'stackedbar', 'pie', 'doughnut'] },
        bound:      { label: 'Grouped',    charts: ['bar', 'vbar', 'line', 'pie', 'doughnut'] },
    };

    /**
     * Exported visualizer class -> the type name its instances report. Lets us
     * ask SurveyJS which visualizers it actually registered for a question and
     * name them, rather than keeping a second copy of that mapping in sync.
     */
    const CTOR_TYPES = {
        WordCloud:        'wordcloud',
        Text:             'text',
        StatisticsTable:  'choices',
        SelectBasePlotly: 'selectBase',
        RankingPlotly:    'selectBase',
        HistogramPlotly:  'histogram',
        GaugePlotly:      'number',
        BooleanPlotly:    'boolean',
        MatrixPlotly:     'matrix',
    };

    const CHART_LABELS = {
        bar:        'Horizontal bar',
        vbar:       'Vertical bar',
        stackedbar: 'Stacked bar',
        line:       'Line',
        pie:        'Pie',
        doughnut:   'Doughnut',
        gauge:      'Gauge',
        bullet:     'Bullet',
    };

    const AGGREGATES = [
        { value: 'sum',   label: 'Sum' },
        { value: 'avg',   label: 'Average' },
        { value: 'count', label: 'Responses' },
        { value: 'min',   label: 'Minimum' },
        { value: 'max',   label: 'Maximum' },
    ];

    const SORTS = [
        { value: 'value_desc', label: 'Value, high to low' },
        { value: 'value_asc',  label: 'Value, low to high' },
        { value: 'label',      label: 'Label, A to Z' },
        { value: 'none',       label: 'Order of submission' },
    ];

    const LIMITS = [
        { value: 0,  label: 'All' },
        { value: 5,  label: 'Top 5' },
        { value: 10, label: 'Top 10' },
        { value: 20, label: 'Top 20' },
    ];

    /** Types that can be summed or averaged against a label. */
    const BINDABLE_TYPES = ['number', 'rating', 'expression'];

    /** Types that read well as the axis of a grouped chart. */
    const LABEL_TYPES = ['text', 'dropdown', 'radiogroup', 'boolean'];

    /* ---------------------------------------------------------------------- *
     |  Question helpers                                                      |
     * ---------------------------------------------------------------------- */

    /**
     * The key SurveyJS Analytics resolves a question against. Text questions
     * resolve on their inputType, so a text question with inputType "number"
     * picks up the numeric visualizers.
     */
    function visualizerKey(question) {
        const type = question.getType();
        return (type === 'text' && question.inputType) ? question.inputType : type;
    }

    let ctorTypes = null;

    /** Constructor -> type name, built once the bundles have loaded. */
    function typeIndex() {
        if (ctorTypes) return ctorTypes;

        ctorTypes = new Map();
        Object.keys(CTOR_TYPES).forEach(name => {
            if (SA[name]) ctorTypes.set(SA[name], CTOR_TYPES[name]);
        });
        ctorTypes.set(BoundVisualizer, BOUND);

        return ctorTypes;
    }

    /**
     * The visualizers SurveyJS registered for a question, in the order it will
     * offer them. Unregistered types fall back to the VisualizerBase stub,
     * which only renders a "no visualizer" notice, so those count as none.
     */
    function registeredFor(question) {
        try {
            const candidates = SA.VisualizationManager.getVisualizersByType(visualizerKey(question));
            return (candidates.length === 1 && candidates[0] === SA.VisualizerBase) ? [] : candidates;
        } catch (e) {
            return [];
        }
    }

    /** Whether SurveyJS knows how to chart this question at all. */
    function isChartable(question) {
        return registeredFor(question).length > 0;
    }

    /** Visualizer choices to offer for a question, as {value, label, charts}. */
    function visualizersFor(question) {
        const index = typeIndex();

        return registeredFor(question)
            .map(ctor => index.get(ctor))
            .filter(type => type && VISUALIZERS[type])
            .map(type => ({ value: type, label: VISUALIZERS[type].label, charts: VISUALIZERS[type].charts }));
    }

    /** Chart types a given visualizer can draw. */
    function chartsFor(visualizer) {
        return (VISUALIZERS[visualizer] || {}).charts || [];
    }

    function isBindable(question) {
        return BINDABLE_TYPES.indexOf(visualizerKey(question)) !== -1;
    }

    function isLabelCandidate(question) {
        return LABEL_TYPES.indexOf(visualizerKey(question)) !== -1;
    }

    /* ---------------------------------------------------------------------- *
     |  Rules                                                                 |
     * ---------------------------------------------------------------------- */

    function defaultRule(question) {
        const options = visualizersFor(question);
        const visualizer = options.length ? options[0].value : null;

        return {
            visible:       true,
            visualizer:    visualizer,
            chart_type:    chartsFor(visualizer)[0] || null,
            bind_label_to: null,
            series:        [],
            aggregate:     'sum',
            sort:          'value_desc',
            limit:         0,
        };
    }

    /**
     * Merge whatever was saved onto a complete rule, so callers never have to
     * probe for missing keys. A question with no saved rule stays visible,
     * which keeps questions added after the rules were saved from silently
     * disappearing from the page.
     */
    function normalizeRule(saved, question) {
        const rule = defaultRule(question);
        if (!saved || typeof saved !== 'object') return rule;

        rule.visible = saved.visible !== false;

        const allowed = visualizersFor(question).map(option => option.value);
        if (saved.visualizer && allowed.indexOf(saved.visualizer) !== -1) {
            rule.visualizer = saved.visualizer;
        }

        const charts = chartsFor(rule.visualizer);
        rule.chart_type = charts.indexOf(saved.chart_type) !== -1 ? saved.chart_type : (charts[0] || null);

        // kept even when another visualizer is selected, so switching away from
        // "Grouped" and back doesn't throw the binding away
        const limit = parseInt(saved.limit, 10);

        rule.bind_label_to = saved.bind_label_to || null;
        rule.series        = Array.isArray(saved.series) ? saved.series.slice() : [];
        rule.aggregate     = AGGREGATES.some(a => a.value === saved.aggregate) ? saved.aggregate : 'sum';
        rule.sort          = SORTS.some(s => s.value === saved.sort) ? saved.sort : 'value_desc';
        rule.limit         = limit > 0 ? limit : 0;

        return rule;
    }

    /** Normalize a whole viz_rules payload against the survey's questions. */
    function normalize(rules, questions) {
        const saved = (rules && typeof rules === 'object') ? rules : {};
        const normalized = {};

        questions.forEach(question => {
            normalized[question.name] = normalizeRule(saved[question.name], question);
        });

        return normalized;
    }

    /* ---------------------------------------------------------------------- *
     |  Grouped visualizer                                                    |
     * ---------------------------------------------------------------------- */

    function toNumber(value) {
        if (value === null || value === undefined || value === '') return null;
        if (typeof value === 'boolean') return value ? 1 : 0;

        const number = typeof value === 'number'
            ? value
            : parseFloat(String(value).replace(/[\s,]/g, ''));

        return isNaN(number) ? null : number;
    }

    function isAnswered(value) {
        if (value === null || value === undefined || value === '') return false;
        return !(Array.isArray(value) && value.length === 0);
    }

    /** Human readable label for a raw answer, resolving choice values to text. */
    function labelOf(question, value) {
        if (!isAnswered(value)) return null;

        if (Array.isArray(value)) {
            const parts = value.map(item => labelOf(question, item)).filter(part => part !== null);
            return parts.length ? parts.join(', ') : null;
        }

        if (typeof value === 'object') return null;

        const choices = question && question.visibleChoices;
        if (choices && choices.length) {
            for (let i = 0; i < choices.length; i++) {
                if (choices[i].value === value) return String(choices[i].text);
            }
        }

        return String(value);
    }

    function aggregateAnswers(answers, how) {
        if (how === 'count') return answers.length;

        const numbers = answers.map(toNumber).filter(number => number !== null);
        if (!numbers.length) return 0;

        const round = value => Math.round(value * 100) / 100;

        switch (how) {
            case 'avg': return round(numbers.reduce((a, b) => a + b, 0) / numbers.length);
            case 'min': return Math.min.apply(null, numbers);
            case 'max': return Math.max.apply(null, numbers);
            default:    return round(numbers.reduce((a, b) => a + b, 0));
        }
    }

    function truncate(text, length) {
        return text.length > length ? text.substring(0, length - 1) + '…' : text;
    }

    /**
     * Plots a numeric question grouped by another question's answer, with any
     * number of extra numeric questions drawn alongside as extra series.
     *
     * The binding arrives through the panel options under the question's own
     * name, which is how SurveyJS hands per question options to a visualizer.
     */
    class BoundVisualizer extends SA.VisualizerBase {

        constructor(question, data, options, type) {
            super(question, data, options || {}, type || BOUND);

            const rule = (this.questionOptions || {}).rule || {};
            const limit = parseInt(rule.limit, 10);

            this.chartTypes = chartsFor(BOUND).slice();
            this._chartType = this.chartTypes.indexOf(rule.chart_type) !== -1
                ? rule.chart_type
                : this.chartTypes[0];

            this._rule = {
                bind_label_to: rule.bind_label_to || null,
                series:        Array.isArray(rule.series) ? rule.series.slice() : [],
                aggregate:     rule.aggregate || 'sum',
                sort:          rule.sort || 'value_desc',
                limit:         limit > 0 ? limit : 0,
            };

            this.registerToolbarItem('changeChartType', () => SA.DocumentHelper.createSelector(
                this.chartTypes.map(chart => ({ value: chart, text: CHART_LABELS[chart] || chart })),
                option => this._chartType === option.value,
                event => this.setChartType(event.target.value)
            ));

            this.registerToolbarItem('changeAggregate', () => SA.DocumentHelper.createSelector(
                AGGREGATES.map(aggregate => ({ value: aggregate.value, text: aggregate.label })),
                option => this._rule.aggregate === option.value,
                event => this.setAggregate(event.target.value)
            ));
        }

        get chartType() { return this._chartType; }
        set chartType(value) { this.setChartType(value); }

        setChartType(value) {
            if (this.chartTypes.indexOf(value) === -1 || this._chartType === value) return;

            this._chartType = value;
            this.stateChanged('chartType', value);
            this.refreshContent();
        }

        setAggregate(value) {
            if (this._rule.aggregate === value) return;

            this._rule.aggregate = value;
            this._calculationsCache = undefined;
            this.stateChanged('aggregate', value);
            this.refreshContent();
        }

        getState() {
            return { chartType: this._chartType, aggregate: this._rule.aggregate };
        }

        setState(state) {
            if (!state) return;
            if (state.chartType && this.chartTypes.indexOf(state.chartType) !== -1) {
                this._chartType = state.chartType;
            }
            if (state.aggregate) this._rule.aggregate = state.aggregate;
        }

        refreshContent() {
            if (this.contentContainer) {
                this.destroyContent(this.contentContainer);
                this.renderContent(this.contentContainer);
            }
            this.invokeOnUpdate();
        }

        destroyContent(container) {
            const chart = container.querySelector('.sa-bound__chart');
            if (chart && global.Plotly) global.Plotly.purge(chart);
            container.innerHTML = '';
        }

        /** Group the (filtered) submissions by the bound label question. */
        getCalculatedValuesCore() {
            const rule = this._rule;
            if (!rule.bind_label_to) return { error: 'unbound' };

            const survey = this.options.survey;
            const labelQuestion = survey ? survey.getQuestionByName(rule.bind_label_to) : null;

            const names = [this.question.name].concat(
                rule.series.filter(name => name && name !== this.question.name)
            );

            const order = [];
            const buckets = new Map();

            (this.data || []).forEach(row => {
                const label = labelOf(labelQuestion, row[rule.bind_label_to]);
                if (label === null) return;

                if (!buckets.has(label)) {
                    buckets.set(label, new Map());
                    order.push(label);
                }

                const bucket = buckets.get(label);

                names.forEach(name => {
                    if (!isAnswered(row[name])) return;
                    if (!bucket.has(name)) bucket.set(name, []);
                    bucket.get(name).push(row[name]);
                });
            });

            let rows = order.map(label => ({
                label: label,
                values: names.map(name => aggregateAnswers(buckets.get(label).get(name) || [], rule.aggregate)),
            }));

            if (rule.sort === 'label') {
                rows.sort((a, b) => a.label.localeCompare(b.label));
            } else if (rule.sort === 'value_desc') {
                rows.sort((a, b) => b.values[0] - a.values[0]);
            } else if (rule.sort === 'value_asc') {
                rows.sort((a, b) => a.values[0] - b.values[0]);
            }

            if (rule.limit > 0) rows = rows.slice(0, rule.limit);

            return {
                labels: rows.map(row => row.label),
                series: names.map((name, index) => {
                    const question = survey ? survey.getQuestionByName(name) : null;
                    return {
                        name:   name,
                        title:  (question && question.title) || name,
                        values: rows.map(row => row.values[index]),
                    };
                }),
                labelTitle: (labelQuestion && labelQuestion.title) || rule.bind_label_to,
            };
        }

        renderContentAsync(container) {
            return this.getCalculatedValues().then(model => {
                if (model.error === 'unbound') {
                    this.renderMessage(container, 'No label field bound yet. Pick one under Visualization rules.');
                } else if (!model.labels.length) {
                    this.renderMessage(container, 'No answers to group yet.');
                } else {
                    this.renderChart(container, model);
                }

                return container;
            });
        }

        renderMessage(container, text) {
            container.appendChild(
                SA.DocumentHelper.createElement('div', 'sa-bound__empty', { textContent: text })
            );
        }

        renderChart(container, model) {
            const chart = SA.DocumentHelper.createElement('div', 'sa-bound__chart');
            container.appendChild(chart);

            const colors = this.getColors();
            const type   = this._chartType;
            const ticks  = model.labels.map(label => truncate(label, this.labelTruncateLength));

            let traces;
            let height = 400;

            if (type === 'pie' || type === 'doughnut') {
                traces = [{
                    type: 'pie',
                    labels: model.labels,
                    values: model.series[0].values,
                    hole: type === 'doughnut' ? 0.4 : 0,
                    marker: { colors: colors },
                    hoverinfo: 'label+value+percent',
                    textposition: 'inside',
                }];
            } else if (type === 'line') {
                traces = model.series.map((serie, index) => ({
                    type: 'scatter',
                    mode: 'lines+markers',
                    name: serie.title,
                    x: ticks,
                    y: serie.values,
                    text: model.labels,
                    hovertemplate: '%{text}<br>' + serie.title + ': %{y}<extra></extra>',
                    marker: { color: colors[index] },
                    line: { color: colors[index] },
                }));
            } else {
                const horizontal = type !== 'vbar';

                traces = model.series.map((serie, index) => ({
                    type: 'bar',
                    name: serie.title,
                    orientation: horizontal ? 'h' : 'v',
                    x: horizontal ? serie.values : ticks,
                    y: horizontal ? ticks : serie.values,
                    text: model.labels,
                    hovertemplate: '%{text}<br>' + serie.title + ': %{'
                        + (horizontal ? 'x' : 'y') + '}<extra></extra>',
                    marker: { color: colors[index] },
                }));

                if (horizontal) {
                    height = Math.max(260, model.labels.length * model.series.length * 26 + 120);
                }
            }

            const layout = {
                font: { family: 'Segoe UI, sans-serif', size: 13, color: '#404040' },
                height: height,
                margin: { l: 10, r: 20, t: 10, b: 40 },
                colorway: colors,
                hovermode: 'closest',
                barmode: 'group',
                bargap: 0.25,
                plot_bgcolor: this.backgroundColor,
                paper_bgcolor: this.backgroundColor,
                showlegend: model.series.length > 1,
                legend: { orientation: 'h', y: -0.15 },
            };

            if (type !== 'pie' && type !== 'doughnut') {
                layout.xaxis = { automargin: true, title: type === 'bar' ? '' : model.labelTitle };
                layout.yaxis = { automargin: true, title: type === 'bar' ? model.labelTitle : '' };
            } else {
                layout.showlegend = true;
            }

            global.Plotly.newPlot(chart, traces, layout, {
                displaylogo: false,
                responsive: true,
                displayModeBar: false,
            });
        }
    }

    /* ---------------------------------------------------------------------- *
     |  Panel                                                                 |
     * ---------------------------------------------------------------------- */

    let registered = false;

    function register() {
        if (registered || !SA) return;

        BINDABLE_TYPES.forEach(type => SA.VisualizationManager.registerVisualizer(type, BoundVisualizer));

        SA.localization.locales.en['visualizer_' + BOUND] = VISUALIZERS[BOUND].label;
        Object.keys(CHART_LABELS).forEach(chart => {
            SA.localization.locales.en['chartType_' + chart] = CHART_LABELS[chart];
        });

        registered = true;
    }

    /** Title shown above a chart card. */
    function titleFor(question, rule, survey) {
        const title = question.title || question.name;
        if (rule.visualizer !== BOUND || !rule.bind_label_to) return title;

        const labelQuestion = survey.getQuestionByName(rule.bind_label_to);
        const by = (labelQuestion && labelQuestion.title) || rule.bind_label_to;
        const extra = (rule.series || []).length;

        return title + (extra ? ' +' + extra + ' more' : '') + ' by ' + by;
    }

    /**
     * Build a VisualizationPanel honouring `rules`.
     *
     * Only the questions a rule keeps visible reach the panel, so hidden ones
     * cost nothing to render, and the element list is handed over explicitly so
     * we control both the ordering and the card titles.
     *
     * Returns the panel, or null when nothing is left to chart.
     */
    function buildPanel(survey, data, rules, options) {
        register();

        const questions = survey.getAllQuestions().filter(question => {
            const rule = rules[question.name];
            return rule && rule.visible && isChartable(question);
        });

        if (!questions.length) return null;

        const panelOptions = Object.assign({
            allowHideQuestions: false,
            hideEmptyAnswers: true,
            survey: survey,
        }, options || {});

        questions.forEach(question => {
            panelOptions[question.name] = { rule: rules[question.name] };
        });

        const elements = questions.map(question => ({
            name: question.name,
            displayName: titleFor(question, rules[question.name], survey),
            isVisible: true,
            isPublic: true,
        }));

        const panel = new SA.VisualizationPanel(questions, data, panelOptions, elements);

        questions.forEach(question => applyRule(panel, question, rules[question.name]));

        return panel;
    }

    /** Point a question at the visualizer and chart type its rule asked for. */
    function applyRule(panel, question, rule) {
        let visualizer = panel.getVisualizer(question.name);
        if (!visualizer) return;

        // questions with more than one visualizer come wrapped in a selector
        if (typeof visualizer.getVisualizers === 'function') {
            const available = visualizer.getVisualizers().map(candidate => candidate.type);

            if (rule.visualizer && available.indexOf(rule.visualizer) !== -1) {
                visualizer.setVisualizer(rule.visualizer, true);
            }

            visualizer = visualizer.visualizer;
        }

        if (visualizer && rule.chart_type && Array.isArray(visualizer.chartTypes)
            && visualizer.chartTypes.indexOf(rule.chart_type) !== -1) {
            visualizer.chartType = rule.chart_type;
        }
    }

    register();

    return {
        BOUND:        BOUND,
        VISUALIZERS:  VISUALIZERS,
        CHART_LABELS: CHART_LABELS,
        AGGREGATES:   AGGREGATES,
        SORTS:        SORTS,
        LIMITS:       LIMITS,

        visualizerKey:    visualizerKey,
        visualizersFor:   visualizersFor,
        chartsFor:        chartsFor,
        isChartable:      isChartable,
        isBindable:       isBindable,
        isLabelCandidate: isLabelCandidate,

        defaultRule: defaultRule,
        normalize:   normalize,
        buildPanel:  buildPanel,
    };

})(window);
