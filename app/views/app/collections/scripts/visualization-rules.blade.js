/**
 * Visualization rules editor
 * --------------------------------------------------------------------------
 * Drives the offcanvas that decides which questions get charted, how, and
 * which numeric questions are bound to a label question. The vocabulary
 * (visualizers, chart types, aggregates) comes from VizEngine so the editor
 * and the panel can never disagree about what is possible.
 */
(function () {
    'use strict';

    const offcanvas = document.getElementById('offcanvasFormRules');
    if (!offcanvas) return;

    const saveUrl = "@route('forms.rules', $form->id)";

    const survey    = new Survey.Model(@json($form->content));
    const questions = survey.getAllQuestions();
    const labels    = questions.filter(question => VizEngine.isLabelCandidate(question));
    const numerics  = questions.filter(question => VizEngine.isBindable(question));

    let rules = VizEngine.normalize(@json($form->viz_rules), questions);

    // questions whose "compare with" list is expanded with nothing picked yet;
    // once a series is picked the rule itself says it's open
    const compareOpen = new Set();

    /* ---------------------------------------------------------------- *
     |  Helpers                                                         |
     * ---------------------------------------------------------------- */

    function escape(text) {
        const node = document.createElement('span');
        node.textContent = text;
        return node.innerHTML;
    }

    function titleOf(question) {
        return question.title || question.name;
    }

    function options(list, selected) {
        return list.map(option =>
            '<option value="' + escape(String(option.value)) + '"'
            + (String(option.value) === String(selected) ? ' selected' : '') + '>'
            + escape(option.label) + '</option>'
        ).join('');
    }

    /* ---------------------------------------------------------------- *
     |  Card rendering                                                  |
     * ---------------------------------------------------------------- */

    function cardBody(question, rule) {
        const visualizers = VizEngine.visualizersFor(question);
        const charts = VizEngine.chartsFor(rule.visualizer).map(chart => ({
            value: chart,
            label: VizEngine.CHART_LABELS[chart] || chart,
        }));

        let html = '<div class="row g-2">';

        if (visualizers.length > 1) {
            html += ''
                + '<div class="col-sm-6">'
                +   '<label class="form-label small text-body-secondary mb-1">Display as</label>'
                +   '<select class="form-select form-select-sm" data-field="visualizer">'
                +       options(visualizers, rule.visualizer)
                +   '</select>'
                + '</div>';
        }

        if (charts.length > 1) {
            html += ''
                + '<div class="col-sm-6">'
                +   '<label class="form-label small text-body-secondary mb-1">Chart type</label>'
                +   '<select class="form-select form-select-sm" data-field="chart_type">'
                +       options(charts, rule.chart_type)
                +   '</select>'
                + '</div>';
        }

        html += '</div>';

        if (rule.visualizer === VizEngine.BOUND) html += boundBlock(question, rule);

        return html;
    }

    /**
     * The binding block. Without it a numeric question charts as a single
     * average over every submission, which says nothing; bound to a label it
     * becomes a value per school, per region, per whatever was collected.
     */
    function boundBlock(question, rule) {
        const labelOptions = [{ value: '', label: 'Choose a field...' }].concat(
            labels.map(label => ({ value: label.name, label: titleOf(label) }))
        );

        const peers = numerics.filter(numeric => numeric.name !== question.name);

        let html = ''
            + '<div class="border-top mt-3 pt-3">'
            +   '<div class="row g-2">'
            +       '<div class="col-sm-6">'
            +           '<label class="form-label small text-body-secondary mb-1">Group by</label>'
            +           '<select class="form-select form-select-sm" data-field="bind_label_to">'
            +               options(labelOptions, rule.bind_label_to || '')
            +           '</select>'
            +       '</div>'
            +       '<div class="col-sm-6">'
            +           '<label class="form-label small text-body-secondary mb-1">Summarize with</label>'
            +           '<select class="form-select form-select-sm" data-field="aggregate">'
            +               options(VizEngine.AGGREGATES, rule.aggregate)
            +           '</select>'
            +       '</div>'
            +       '<div class="col-sm-6">'
            +           '<label class="form-label small text-body-secondary mb-1">Sort</label>'
            +           '<select class="form-select form-select-sm" data-field="sort">'
            +               options(VizEngine.SORTS, rule.sort)
            +           '</select>'
            +       '</div>'
            +       '<div class="col-sm-6">'
            +           '<label class="form-label small text-body-secondary mb-1">Show</label>'
            +           '<select class="form-select form-select-sm" data-field="limit">'
            +               options(VizEngine.LIMITS, rule.limit)
            +           '</select>'
            +       '</div>'
            +   '</div>';

        if (!rule.bind_label_to) {
            html += '<p class="small text-warning-emphasis mt-2 mb-0">'
                +       '<i class="fa-solid fa-triangle-exclamation me-1"></i>'
                +       'Pick a field to group by, otherwise this chart stays empty.'
                +   '</p>';
        }

        if (peers.length) html += compareBlock(question, rule, peers);

        return html + '</div>';
    }

    /**
     * Extra series. Collapsed behind a single toggle so a form with many
     * numeric questions doesn't dump a checkbox per question into every card;
     * the list only appears once someone asks to compare.
     */
    function compareBlock(question, rule, peers) {
        const comparing = rule.series.length > 0 || compareOpen.has(question.name);
        const toggleId  = 'compare-' + escape(question.name);

        let html = ''
            + '<div class="mt-3">'
            +   '<div class="form-check mb-0">'
            +       '<input class="form-check-input" type="checkbox" data-field="compare"'
            +           ' id="' + toggleId + '"' + (comparing ? ' checked' : '') + '>'
            +       '<label class="form-check-label small" for="' + toggleId + '">'
            +           'Compare with other questions'
            +       '</label>'
            +   '</div>';

        if (comparing) {
            const checkboxes = peers.map(peer => {
                const id = 'series-' + escape(question.name + '-' + peer.name);
                const checked = rule.series.indexOf(peer.name) !== -1 ? ' checked' : '';

                return ''
                    + '<div class="form-check mb-0">'
                    +   '<input class="form-check-input" type="checkbox" data-field="series"'
                    +       ' id="' + id + '" value="' + escape(peer.name) + '"' + checked + '>'
                    +   '<label class="form-check-label small" for="' + id + '">'
                    +       escape(titleOf(peer))
                    +   '</label>'
                    + '</div>';
            }).join('');

            html += '<div class="d-flex flex-wrap gap-3 mt-2 ps-4">' + checkboxes + '</div>';
        }

        return html + '</div>';
    }

    function buildCard(question) {
        const rule = rules[question.name];
        const chartable = VizEngine.isChartable(question);

        const card = document.createElement('div');
        card.className = 'rule-card border rounded-3 mb-3 overflow-hidden';
        card.dataset.questionName = question.name;

        card.innerHTML = ''
            + '<div class="rule-card-header d-flex align-items-center gap-2 px-3 py-2 bg-body-secondary">'
            +   '<span class="badge bg-secondary text-white small">' + escape(question.getType()) + '</span>'
            +   '<span class="fw-medium small text-truncate flex-grow-1">' + escape(titleOf(question)) + '</span>'
            +   (chartable
                    ? '<div class="form-check form-switch mb-0">'
                    +     '<input class="form-check-input" type="checkbox" data-field="visible"'
                    +         (rule.visible ? ' checked' : '') + '>'
                    + '</div>'
                    : '<span class="small text-body-tertiary">Not chartable</span>')
            + '</div>'
            + (chartable ? '<div class="rule-card-body px-3 py-3"></div>' : '');

        if (chartable) refreshBody(card, question);

        return card;
    }

    function refreshBody(card, question) {
        const rule = rules[question.name];
        const body = card.querySelector('.rule-card-body');
        if (!body) return;

        card.classList.toggle('is-hidden', !rule.visible);
        body.classList.toggle('d-none', !rule.visible);
        body.innerHTML = cardBody(question, rule);
    }

    function render() {
        compareOpen.clear();

        const list = document.getElementById('rulesList');
        list.innerHTML = '';
        questions.forEach(question => list.appendChild(buildCard(question)));

        document.getElementById('rulesLoading').classList.add('d-none');
        document.getElementById('rulesEditor').classList.remove('d-none');

        updateSummary();
    }

    function updateSummary() {
        const chartable = questions.filter(question => VizEngine.isChartable(question));
        const shown = chartable.filter(question => rules[question.name].visible);

        document.getElementById('rulesSummary').textContent =
            shown.length + ' of ' + chartable.length + ' questions charted';
    }

    /* ---------------------------------------------------------------- *
     |  Editing                                                         |
     * ---------------------------------------------------------------- */

    function onFieldChange(event) {
        const input = event.target.closest('[data-field]');
        if (!input) return;

        const card = input.closest('.rule-card');
        if (!card) return;

        const question = survey.getQuestionByName(card.dataset.questionName);
        const rule = rules[question.name];
        const field = input.dataset.field;

        if (field === 'series') {
            const index = rule.series.indexOf(input.value);
            if (input.checked && index === -1) rule.series.push(input.value);
            if (!input.checked && index !== -1) rule.series.splice(index, 1);
            return;
        }

        // the toggle isn't saved; closing it drops the picks so nothing hidden
        // sneaks into the chart
        if (field === 'compare') {
            if (input.checked) {
                compareOpen.add(question.name);
            } else {
                compareOpen.delete(question.name);
                rule.series = [];
            }
            refreshBody(card, question);
            return;
        }

        if (field === 'visible') {
            rule.visible = input.checked;
            refreshBody(card, question);
            updateSummary();
            return;
        }

        if (field === 'limit') {
            rule.limit = parseInt(input.value, 10) || 0;
            return;
        }

        rule[field] = input.value || null;

        // a new visualizer brings its own chart types, and only the grouped one
        // has a binding, so redraw the body whenever it changes
        if (field === 'visualizer') {
            rules[question.name] = VizEngine.normalize(
                { [question.name]: rule }, [question]
            )[question.name];

            refreshBody(card, question);
        }

        if (field === 'bind_label_to') refreshBody(card, question);
    }

    function setAll(visible) {
        questions.forEach(question => {
            if (VizEngine.isChartable(question)) rules[question.name].visible = visible;
        });

        render();
    }

    /* ---------------------------------------------------------------- *
     |  Saving                                                          |
     * ---------------------------------------------------------------- */

    async function save() {
        const button = document.getElementById('saveRulesBtn');
        const original = button.innerHTML;

        button.disabled = true;
        button.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Saving...';

        try {
            const response = await fetch(saveUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf_token,
                },
                body: JSON.stringify({ viz_rules: rules }),
            });

            const payload = await response.json();
            if (!response.ok || payload.status === false) {
                throw new Error(payload.message || 'Failed to save rules');
            }

            toast.success({ message: payload.message || 'Visualization rules saved' });

            if (typeof window.renderVizFromRules === 'function') {
                window.renderVizFromRules(rules);
            }

            bootstrap.Offcanvas.getOrCreateInstance(offcanvas).hide();
        } catch (error) {
            toast.error({ message: error.message || 'Failed to save rules' });
        } finally {
            button.disabled = false;
            button.innerHTML = original;
        }
    }

    /* ---------------------------------------------------------------- *
     |  Wiring                                                          |
     * ---------------------------------------------------------------- */

    // reopening starts from what the page is actually showing, so cancelling
    // throws the edits away rather than keeping them around in memory
    offcanvas.addEventListener('show.bs.offcanvas', function () {
        if (typeof window.currentVizRules === 'function') {
            rules = JSON.parse(JSON.stringify(window.currentVizRules()));
        }

        render();
    });

    document.getElementById('rulesList').addEventListener('change', onFieldChange);
    document.getElementById('saveRulesBtn').addEventListener('click', save);
    document.getElementById('rulesEnableAll').addEventListener('click', () => setAll(true));
    document.getElementById('rulesDisableAll').addEventListener('click', () => setAll(false));

})();
