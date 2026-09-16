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
    placeholderDiv.innerHTML = `<div style="text-align: center; padding: 3rem;">
        <h3>No Report Available</h3>
        <p style="color: #666;">This report has not been created yet or the data is not available.</p>
    </div>`;
    
    container.appendChild(placeholderDiv);
}

function renderReport(json, containerId) {
    const container = document.getElementById(containerId);
    
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
    if (surveyResults && surveyResults.length > 0) {
        vizPanel.render(document.getElementById("surveyVizPanel"));
    }

    document.querySelectorAll('.report-attachements').forEach(function (element) {
        const attsAttr = element.getAttribute('data-report-atts'); 
        const atts = attsAttr ? attsAttr.split(',') : [];
        
        atts.forEach(att => {
            const questionElement = document.querySelector(`[data-question="${att}"]`);
            if(questionElement) {
                const wrapper = document.createElement('div');
                wrapper.appendChild(questionElement);
                element.appendChild(wrapper);

                document.querySelectorAll('.sa-question__title').forEach(el => {
                    const html = el.innerHTML;
                    const firstBreak = html.indexOf('<br');
                    if (firstBreak !== -1) {
                        el.innerHTML = html.substring(0, firstBreak);
                    }
                });
            }
        });
    });
});