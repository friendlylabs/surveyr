const creatorOptions = {
    showLogicTab: true,
    // showTranslationTab: true,
    isAutoSave: true
};

const creator = new SurveyCreator.SurveyCreator(creatorOptions);
creator.text = JSON.stringify(@json($form->content), null, 2);

// Function to create the logo element
function createLogoElement() {

    const logoElement = document.getElementById('svcLogo');
    if(logoElement) {
        return false;
    }

    const logoContainer = document.createElement('div');
    logoContainer.id = 'svcLogo';

    // Create an anchor element
    const logoLink = document.createElement('a');
    logoLink.href = '/'; 
    logoLink.style.display = 'flex';
    logoLink.style.alignItems = 'center'; 

    // Create an image element (logo)
    const logo = document.createElement('img');
    logo.src = '/assets/images/brand/logo-dark.png';
    logo.alt = 'Logo';
    logo.style.height = '40px'; 
    logo.style.margin = '0 10px';
    logo.style.marginTop = '10px';
    
    logoLink.appendChild(logo); // Append logo to the anchor link
    logoContainer.appendChild(logoLink); // Append anchor link to the logo container
    
    return logoContainer;
}

//Observer to modify the menu after rendering
const observer = new MutationObserver((mutations, obs) => {
    const svcMenuTab = document.querySelector('.svc-tabbed-menu');
    if (svcMenuTab) {
        setTimeout(() => {
            // Inject logo
            const logoElement = createLogoElement();

            if(logoElement) {
                svcMenuTab.prepend(logoElement);
            }

            obs.disconnect(); // Stop observing once modifications are made
        }, 100); // Delay to ensure the menu is rendered
    }
});

observer.observe(document.getElementById("surveyCreator"), {
    childList: true,
    subtree: true
});

creator.saveSurveyFunc = function () {
    const formContent = creator.text;

    $.ajax({
        url: "{{ route('forms.update', $form->id) }}",
        type: 'POST',
        data: {
            _token: csrf_token,
            content: formContent,
            questions: JSON.stringify(creator.survey.getAllQuestions())
        },
        success: function (response) {
            if(!response.status) {
                return console.log(response);
            }
        },
        error: function (error) {
            console.log('An error occurred while saving the form.');
        }
    });
};

document.addEventListener("DOMContentLoaded", function() {

    // delete ai_agent_editor_content from localStorage if it exists
    if (localStorage.getItem("ai_agent_editor_content")) {
        localStorage.removeItem("ai_agent_editor_content");
    }

    // Register the component
    SurveyUI.ReactElementFactory.Instance.registerElement(
        "svc-tab-ai-agent",
        (props) => React.createElement(AiAgentTabComponent, props)
    );

    // Define the tab plugin
    const aiAgentPlugin = {
        activate: () => {},
        deactivate: () => true
    };

    // Add the tab to creator
    creator.addTab({
        name: "ai-agent",
        plugin: aiAgentPlugin,
        title: "AI Editor",
        componentName: "svc-tab-ai-agent",
        index: 3
    });

    creator.render("surveyCreator");
});