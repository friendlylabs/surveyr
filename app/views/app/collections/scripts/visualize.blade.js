const vizPanelOptions = {
    allowHideQuestions: false
}

const surveyJson = JSON.parse((`{!! json_encode($form->content) !!}`).replace(/^\s+|\s+$/gm, '').split('\n').join(''));
const surveyResults = JSON.parse((`{!! json_encode($collections) !!}`).replace(/^\s+|\s+$/gm, '').split('\n').join(''));
const tabsInfo = JSON.parse(`{!! json_encode($tabsInfo) !!}`);

const survey = new Survey.Model(surveyJson);
const vizPanel = new SurveyAnalytics.VisualizationPanel(
    survey.getAllQuestions(),
    surveyResults,
    vizPanelOptions
);

document.addEventListener("DOMContentLoaded", function() {
    vizPanel.render(document.getElementById("surveyVizPanel"));
});