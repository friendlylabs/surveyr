const vizPanelOptions = {
    allowHideQuestions: false
}

const surveyJson = @json($form->content);
const surveyResults = @json($collections);
const tabsInfo = JSON.parse(`{!! json_encode($tabsInfo) !!}`);

const survey = new Survey.Model(surveyJson);
const vizPanel = new SurveyAnalytics.VisualizationPanel(
    survey.getAllQuestions(),
    surveyResults,
    vizPanelOptions
);

document.addEventListener("DOMContentLoaded", function () {
    vizPanel.render(document.getElementById("surveyVizPanel"));        
});