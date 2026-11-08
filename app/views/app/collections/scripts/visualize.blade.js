/**
 * Collection visualization
 * --------------------------------------------------------------------------
 * Renders the form's submissions through VizEngine, which honours the
 * visualization rules saved on the form instead of charting every question.
 */

const surveyJson    = @json($form->content);
const surveyResults = @json($collections);
const savedVizRules = @json($form->viz_rules);

const survey = new Survey.Model(surveyJson);

let vizPanel = null;
let vizRules = VizEngine.normalize(savedVizRules, survey.getAllQuestions());

/**
 * (Re)draw the whole panel. Called on load and again whenever the rules
 * offcanvas saves, so a change shows up without a page reload.
 */
function renderVizFromRules(rules) {
    const host = document.getElementById('surveyVizPanel');
    if (!host) return;

    if (rules) vizRules = VizEngine.normalize(rules, survey.getAllQuestions());

    if (vizPanel) {
        try { vizPanel.layoutEngine.stop(); } catch (e) { /* nothing rendered yet */ }
        vizPanel = null;
    }

    host.innerHTML = '';

    if (!surveyResults.length) {
        return renderVizPlaceholder(host, 'No submissions yet',
            'Charts appear here once this form starts collecting responses.');
    }

    vizPanel = VizEngine.buildPanel(survey, surveyResults, vizRules);

    if (!vizPanel) {
        return renderVizPlaceholder(host, 'Nothing to visualize',
            'Every question is switched off. Open Visualization rules to pick what to chart.');
    }

    vizPanel.render(host);
}

function renderVizPlaceholder(host, title, message) {
    host.innerHTML = `
        <div class="text-center py-6">
            <i class="fa-solid fa-chart-simple fs-4 text-body-tertiary mb-3 d-block"></i>
            <h5 class="fs-8 mb-1">${title}</h5>
            <p class="text-body-tertiary mb-0">${message}</p>
        </div>`;
}

document.addEventListener('DOMContentLoaded', function () {
    renderVizFromRules(null);
});

// the rules offcanvas calls back into this after a successful save
window.renderVizFromRules = renderVizFromRules;
window.currentVizRules = () => vizRules;
