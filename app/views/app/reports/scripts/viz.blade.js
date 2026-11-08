/**
 * Report visuals
 * --------------------------------------------------------------------------
 * Mounts one SurveyJS Analytics visualizer per report attachment, straight
 * into the slot the section gives it. Replaces the old approach of rendering
 * a whole VisualizationPanel into a hidden container and moving the chart
 * nodes around the DOM afterwards.
 *
 * Every mounted chart is a handle: { el, viz, setChartType, setSort, updateData,
 * resize, destroy, ... }. The editor keeps those handles alive across
 * re-renders, so typing in a section never re-plots its charts.
 */
window.ReportViz = (function (global) {
    'use strict';

    const SA = global.SurveyAnalytics;

    let configured = false;

    /** One-time Plotly/analytics setup shared by every chart on the page. */
    function configure() {
        if (configured || !SA) return;
        configured = true;

        // series colours: brand teal first, then distinct hues that hold up in both themes
        if (SA.VisualizerBase) {
            SA.VisualizerBase.customColors = [
                '#19b394', '#3c878f', '#f5a623', '#8e6bd6', '#e2607b',
                '#4f9de6', '#6fd8c2', '#b98f3c', '#8aa39c', '#0e8570',
            ];
        }

        // charts are read-only in reports: no modebar, no toolbars
        ['SelectBasePlotly', 'GaugePlotly', 'HistogramPlotly', 'MatrixPlotly', 'BooleanPlotly', 'RankingPlotly']
            .forEach(name => { if (SA[name]) SA[name].displayModeBar = false; });

        // print media narrows every slot; Plotly only listens to window.resize
        const resizeAllPlots = () => {
            if (!global.Plotly) return;
            document.querySelectorAll('.rp-att-slot .js-plotly-plot').forEach(plot => {
                try { global.Plotly.Plots.resize(plot); } catch (e) { /* not drawn */ }
            });
        };
        global.addEventListener('beforeprint', resizeAllPlots);
        global.addEventListener('afterprint', resizeAllPlots);

        if (SA.PlotlySetup && SA.PlotlySetup.onPlotCreating) {
            SA.PlotlySetup.onPlotCreating.add(function (model, options) {
                const style = getComputedStyle(document.documentElement);
                const text = style.getPropertyValue('--rp-text2').trim() || '#404040';

                options.config = Object.assign({}, options.config, { displayModeBar: false, responsive: true });
                options.layout = Object.assign({}, options.layout, {
                    paper_bgcolor: 'rgba(0,0,0,0)',
                    plot_bgcolor:  'rgba(0,0,0,0)',
                    font: Object.assign({}, options.layout && options.layout.font, {
                        family: 'inherit', color: text,
                    }),
                });
            });
        }
    }

    /** The visualizer that actually draws, unwrapped from an alternatives selector. */
    function inner(viz) {
        return (viz && typeof viz.getVisualizers === 'function') ? viz.visualizer : viz;
    }

    function hideToolbars(viz) {
        if (!viz) return;
        viz.showToolbar = false;
        if (typeof viz.getVisualizers === 'function') {
            viz.getVisualizers().forEach(v => { v.showToolbar = false; });
        }
    }

    /** Map the attachment's sort onto the bound visualizer's rule vocabulary. */
    function ruleSort(sort, rule) {
        if (sort === 'desc') return 'value_desc';
        if (sort === 'asc')  return 'value_asc';
        return (rule && rule.sort) || 'value_desc';
    }

    function notice(slot, text) {
        slot.innerHTML = '<div class="rp-att-notice">' + ReportDoc.escapeHtml(text) + '</div>';
    }

    /**
     * Render an attachment into `slot`.
     *
     * @param {HTMLElement} slot        must be attached to the document and visible
     * @param {object}      attachment  as returned by ReportDoc.resolveAttachment
     * @param {object}      ctx         the attachment's form context { survey, data, … }
     */
    function mount(slot, attachment, ctx) {
        configure();

        slot.innerHTML = '';
        slot.classList.add('rp-att-slot');

        if (!ctx || !ctx.survey) {
            notice(slot, 'This form is no longer part of the report.');
            return stub(slot, attachment);
        }

        const question = ctx.survey.getQuestionByName(attachment.question);

        if (!question) {
            notice(slot, 'This question no longer exists in the form.');
            return stub(slot, attachment);
        }

        if (!VizEngine.isChartable(question)) {
            notice(slot, 'This question type cannot be charted.');
            return stub(slot, attachment);
        }

        const rule = Object.assign({}, attachment.rule || {}, { sort: ruleSort(attachment.sort, attachment.rule) });

        const options = {
            survey:             ctx.survey,
            hideEmptyAnswers:   true,
            allowSelection:     false,
            allowHideQuestions: false,
            answersOrder:       attachment.sort || 'default',
            labelTruncateLength: 40,
        };
        options[question.name] = { rule };

        let viz;
        try {
            viz = SA.VisualizerFactory.createVisualizer(question, ctx.data || [], options);
        } catch (e) {
            console.error('ReportViz: failed to create visualizer', e);
            notice(slot, 'This visual could not be rendered.');
            return stub(slot, attachment);
        }

        VizEngine.applyRuleTo(viz, { visualizer: attachment.visualizer, chart_type: attachment.chart_type });
        hideToolbars(viz);

        viz.render(slot);

        // SurveyJS only re-sizes plots on window.resize; the report grid
        // changes shape without that (layout flips, rail toggles, drag)
        let frame = null;
        const observer = ('ResizeObserver' in global) ? new ResizeObserver(() => {
            if (frame) cancelAnimationFrame(frame);
            frame = requestAnimationFrame(() => { frame = null; handle.resize(); });
        }) : null;
        if (observer) observer.observe(slot);

        const handle = {
            id:       attachment.id,
            question: question,
            el:       slot,
            viz:      viz,

            inner() { return inner(viz); },

            /** Chart types the current visualizer can draw. */
            chartTypes() {
                const v = inner(viz);
                return (v && Array.isArray(v.chartTypes)) ? v.chartTypes.slice() : [];
            },

            chartType() {
                const v = inner(viz);
                return v ? (v.chartType || null) : null;
            },

            setChartType(type) {
                const v = inner(viz);
                if (!v || !Array.isArray(v.chartTypes) || v.chartTypes.indexOf(type) === -1) return false;
                if (typeof v.setChartType === 'function') v.setChartType(type); else v.chartType = type;
                return true;
            },

            /** Visualizer types offered by an alternatives selector, if any. */
            visualizers() {
                return typeof viz.getVisualizers === 'function' ? viz.getVisualizers().map(v => v.type) : [];
            },

            visualizer() {
                const v = inner(viz);
                return v ? v.type : null;
            },

            setVisualizer(type) {
                if (typeof viz.setVisualizer !== 'function' || this.visualizers().indexOf(type) === -1) return false;
                viz.setVisualizer(type);
                hideToolbars(viz);
                return true;
            },

            /** Whether the current visualizer can reorder its answers. */
            supportsSort() {
                const v = inner(viz);
                if (!v) return false;
                if (v.type === VizEngine.BOUND) return true;
                return typeof v.isSupportAnswersOrder === 'function' && v.isSupportAnswersOrder();
            },

            setSort(sort) {
                const v = inner(viz);
                if (!v) return;
                if (v.type === VizEngine.BOUND) {
                    v.setSort(ruleSort(sort, attachment.rule));
                } else if ('answersOrder' in v) {
                    v.answersOrder = sort;
                }
            },

            /** Swap the submissions behind the chart; each visualizer re-plots itself. */
            updateData(rows) {
                const list = typeof viz.getVisualizers === 'function' ? viz.getVisualizers() : [viz];
                list.forEach(v => v.updateData(rows || []));
            },

            resize() {
                if (!global.Plotly) return;
                slot.querySelectorAll('.js-plotly-plot').forEach(plot => {
                    try { global.Plotly.Plots.resize(plot); } catch (e) { /* not drawn yet */ }
                });
            },

            destroy() {
                if (observer) observer.disconnect();
                try { viz.destroy(); } catch (e) { /* already gone */ }
                slot.innerHTML = '';
            },
        };

        return handle;
    }

    /** Handle for a slot that shows a notice instead of a chart. */
    function stub(slot, attachment) {
        return {
            id: attachment.id, question: null, el: slot, viz: null,
            inner() { return null; },
            chartTypes() { return []; }, chartType() { return null; }, setChartType() { return false; },
            visualizers() { return []; }, visualizer() { return null; }, setVisualizer() { return false; },
            supportsSort() { return false; }, setSort() {},
            updateData() {}, resize() {},
            destroy() { slot.innerHTML = ''; },
        };
    }

    return { mount, configure };

})(window);
