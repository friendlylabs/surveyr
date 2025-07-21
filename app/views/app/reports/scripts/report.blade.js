const surveyJson = @json($report->form->content);
const surveyResults = @json($collections);
const surveyReport = @json($reportContent);

const survey = new Survey.Model(surveyJson);
const vizPanel = new SurveyAnalytics.VisualizationPanel(
    survey.getAllQuestions(),
    surveyResults
);

function renderReportPlaceholder(containerId) {
  const container = document.getElementById(containerId);
  
  const placeholderDiv = document.createElement("div");
  placeholderDiv.className = "report-placeholder w-100";
  placeholderDiv.innerHTML = `@include('components.empty', [
        'alertTitle' => 'No Report Available',
        'alertMessage' => 'This report has not been created yet or the data is not available.',
    ])`;
  
  container.appendChild(placeholderDiv);
}

function renderReport(json, containerId) {
  const container = document.getElementById(containerId);
  
  // Check if report data exists
  if (!json || !json.menu || !json.sections) {
    renderReportPlaceholder(containerId);
    return;
  }
  
  // Render Navigation
  const nav = document.createElement("nav");
  nav.className = "doc-menu";
  const ul = document.createElement("ul");
  ul.className = "doc-menu-list";
  json.menu.forEach(item => {
    const li = document.createElement("li");
    const a = document.createElement("a");
    a.href = `#${item.target}`;
    a.textContent = item.label;
    li.appendChild(a);
    ul.appendChild(li);
  });
  nav.appendChild(ul);
  container.appendChild(nav);

  // Render Sections
  const content = document.createElement("div");
  content.className = "doc-content";
  json.sections.forEach(section => {
    const sectionDiv = document.createElement("div");
    sectionDiv.className = "report-row";
    sectionDiv.id = section.id;

    const descDiv = document.createElement("div");
    descDiv.className = "report-description";
    // Convert newlines in description to <br> for display
    const descriptionHtml = (section.description || "").replace(/\n/g, '<br>');
    descDiv.innerHTML = `<h2>${section.title}</h2><p>${descriptionHtml}</p>`;

    const attsDiv = document.createElement("div");
    attsDiv.className = "report-attachements";
    attsDiv.setAttribute("data-report-atts", section.attachments.join(","));

    sectionDiv.appendChild(descDiv);
    sectionDiv.appendChild(attsDiv);
    content.appendChild(sectionDiv);
  });

  container.appendChild(content);
}

// Render the report or placeholder
if (surveyReport && Object.keys(surveyReport).length > 0) {
  renderReport(surveyReport, "reportContainer");
} else {
  renderReportPlaceholder("reportContainer");
}

document.addEventListener("DOMContentLoaded", async function() {
    // Only render viz panel if we have survey data
    if (surveyResults && surveyResults.length > 0) {
      vizPanel.render(document.getElementById("surveyVizPanel"));
    }

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