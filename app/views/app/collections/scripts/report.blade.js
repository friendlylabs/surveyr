//hideQn: false, dragging: false
const vizPanelOptions = {
    allowHideQuestions: false,
    allowDragDrop: false,        
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

document.addEventListener("DOMContentLoaded", async function() {
    vizPanel.render(document.getElementById("surveyVizPanel"));

    document.querySelectorAll('.report-attachements').forEach(function (element) {
        const attsAttr = element.getAttribute('data-report-atts'); 
            // sample: data-report-atts="schoolName,courseRating", data-report-type="vizPanel._element.item.name"

        const atts = attsAttr ? attsAttr.split(',') : [];
        atts.forEach(att => {

            // get [data-question="att"] element
            const questionElement = document.querySelector(`[data-question="${att}"]`);
            if( questionElement ) {
                // move and append it to this element
                const wrapper = document.createElement('div');
                wrapper.appendChild(questionElement);
                element.appendChild(wrapper);
            }


        });
    });
        
});