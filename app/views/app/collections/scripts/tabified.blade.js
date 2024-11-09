const vizPanelOptions = {
    allowHideQuestions: false
};

// Parse survey and results data
const surveyJson = JSON.parse(`{!! json_encode($form->content) !!}`);
const surveyResults = JSON.parse(`{!! json_encode($collections) !!}`);
const tabInfo = JSON.parse(`{!! json_encode($tabsInfo) !!}`);

const survey = new Survey.Model(surveyJson);

// Array to store created VisualizationPanels to avoid redundant re-rendering
const vizPanels = {};

// Function to render visualization for a specific tab
function renderTabVisualization(tab, index) {
    // Check if the VisualizationPanel for this tab has already been created
    if (!vizPanels[index]) {
        // Filter questions for the current tab
        const questions = survey.getAllQuestions().filter(q => tab.questions.includes(q.name));
        
        // Create a new VisualizationPanel with tab-specific questions
        vizPanels[index] = new SurveyAnalytics.VisualizationPanel(
            questions,
            surveyResults,
            vizPanelOptions
        );

        // Create a div to hold the visualization for this tab if it doesn't exist
        let tabContent = document.getElementById(`tabContent-${index}`);
        if (!tabContent) {
            tabContent = document.createElement("div");
            tabContent.id = `tabContent-${index}`;
            tabContent.style.display = "none";  // Hide initially
            document.getElementById("surveyVizPanel").appendChild(tabContent);
        }

        // Render the VisualizationPanel in the tab content
        vizPanels[index].render(tabContent);
    }
}

// Event listener to handle tab switching
function switchTab(index) {
    // Hide all tab contents
    tabInfo.forEach((_, i) => {
        const tabContent = document.getElementById(`tabContent-${i}`);
        if (tabContent) {
            tabContent.style.display = "none";
        }
    });
    
    // Show selected tab content and render its VisualizationPanel if not already done
    const selectedTabContent = document.getElementById(`tabContent-${index}`);
    // selectedTabContent.style.display = "block";
    renderTabVisualization(tabInfo[index], index);  // Ensure the panel is rendered
}

// Set up tabs on DOMContentLoaded
document.addEventListener("DOMContentLoaded", function() {
    const tabContainer = document.createElement("div");
    tabContainer.className = "tab-container";

    // Create tab buttons and initialize the first tab's visualization
    tabInfo.forEach((tab, index) => {
        // Create a button for each tab
        const tabButton = document.createElement("button");
        tabButton.textContent = tab.name;
        tabButton.onclick = () => switchTab(index);
        tabContainer.appendChild(tabButton);
    });

    // Add tab container to the DOM and render the first tab by default
    document.getElementById("surveyVizPanel").prepend(tabContainer);
    switchTab(0);  // Render the first tab on load
});