/**
 * Report viewer bootstrap: shared by the in-app viewer and the public page.
 * Expects $report, $reportContent and $forms (one bundle per source form the
 * document references: id, title, survey, rules, flags, data) in scope, and
 * optionally $svql (SurveyQL sources/expressions for inline query tokens).
 */
(function () {
    const bundles = {!! json_encode($forms, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!};
    const doc     = {!! json_encode($reportContent, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!};
    const svql    = {!! json_encode($svql ?? null, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!};
    const showQueryErrors = {{ !empty($isPreview) ? 'true' : 'false' }};

    // one context per form: the survey model, its viz rules and its submissions
    const ctxs = {};
    (Array.isArray(bundles) ? bundles : []).forEach(bundle => {
        const survey = new Survey.Model(bundle.survey || {});
        ctxs[bundle.id] = {
            id:      bundle.id,
            title:   bundle.title,
            json:    bundle.survey || {},
            survey:  survey,
            rules:   VizEngine.normalize(bundle.rules, survey.getAllQuestions()),
            flags:   bundle.flags || [],
            data:    Array.isArray(bundle.data) ? bundle.data : [],
        };
    });

    const host = document.getElementById('reportHost');

    function draw() {
        const hasQueries = window.ReportQuery && ReportQuery.tokens(doc).length > 0;

        // charts first; query answers are filled in once SurveyQL has run
        ReportRenderer.render(host, doc, ctxs, {
            title:        {!! json_encode($report->title, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!},
            emptyHtml:    host.dataset.emptyHtml || '',
            queryPending: hasQueries,
        });
        if (!hasQueries) return;

        ReportQuery.evaluate(doc, svql, ctxs).then(queries => {
            ReportRenderer.fillQueries(host, doc, { queries, queryErrors: showQueryErrors });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', draw);
    } else {
        draw();
    }
})();
