window.React = { createElement: SurveyUI.createElement };

// Global variable to store the CKEditor instance
let ckeditorInstance = null;
const LOCALSTORAGE_KEY = 'ai_agent_editor_content';

class AiAgentTabComponent extends SurveyUI.Component {
  constructor(props) {
    super(props);
    this.state = {
      mode: "generate", // or 'edit'
      prompt: "",
      isLoading: false
    };
  }

  componentDidMount() {
    // Load content from localStorage on component mount
    const savedContent = localStorage.getItem(LOCALSTORAGE_KEY);
    if (savedContent) {
      this.setState({ prompt: savedContent });
    }
  }

  handlePromptChange(event) {
    const content = event.target.value;
    this.setState({ prompt: content });
    // Save to localStorage whenever content changes
    localStorage.setItem(LOCALSTORAGE_KEY, content);
  }

  handleModeChange(event) {
    this.setState({ mode: event.target.value });
  }

  async sendPrompt() {
    this.setState({ isLoading: true });

    const { mode } = this.state;
    
    // Warn user if mode is generate
    if (mode === "generate") {
      const confirmed = confirm("Warning: Generating a new form will completely replace the current form. Do you want to continue?");
      if (!confirmed) {
        this.setState({ isLoading: false });
        return;
      }
    }
    
    // Get content from CKEditor instance if available, otherwise from state
    let content = this.state.prompt;
    if (ckeditorInstance) {
      content = ckeditorInstance.getData();
    }
    
    // Extract title and description
    let title = $('.ck-content h1').first().text();
    let description = content;

    if (title.trim() == '' || description.trim() == '') {
      toast.error({message: 'Please provide a title and description for the form'});
      this.setState({ isLoading: false });
      return;
    }

    // Remove first 2 lines of the description
    description = description.split('\n').slice(2).join('\n');
    
    const payload = {
      title: title,
      description: description,
      mode: mode
    };

    if (mode === "edit") {
      payload.survey = this.props.creator.JSON;
    }

    try {
      // Replace with your actual backend endpoint
      const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute("content");
      payload._token = csrfToken;
      const response = await fetch("@route('forms.generate')", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload)
      });

      const result = await response.json();

      // Check if the request was successful
      if (result.status) {
        // Validate that we have a valid survey response
        if (result.survey) {
          // Replace the current survey JSON
          this.props.creator.JSON = result.survey;
          this.props.creator.switchTab("designer");
          
          // Clear the editor content after successful submission
          if (ckeditorInstance) {
            ckeditorInstance.setData('');
          }
          this.setState({ prompt: '' });
          
          // Show success message if provided
          if (result.message) {
            toast.success({ message: result.message });
          }
        } else {
          // Success status but no valid survey data
          toast.error({ message: "Invalid response: No survey data received from server." });
        }
      } else {
        // Show error message from server
        const errorMessage = result.message || "An error occurred while processing your request.";
        toast.error({ message: errorMessage });
      }
    } catch (e) {
      toast.error({ message: "An error occurred while processing your request." });
    }

    this.setState({ isLoading: false });
  }

  render() {
    const containerStyle = { width: "100%", padding: "10px", height: "90%", display: "flex", flexDirection: "column" };
    const editorStyle = { flexGrow: 1, marginTop: "10px" };

    return (
      SurveyUI.createElement("div", { style: containerStyle },
        // Floating select input
        SurveyUI.createElement("div", { style: { 
            display: "flex", 
            justifyContent: "space-between", 
            alignItems: "center", 
            position: "absolute", 
            width: "100%", 
            maxWidth: "190px", 
            bottom: "10px", 
            right: "9px" 
          } },
          SurveyUI.createElement("select", {
            value: this.state.mode,
            onChange: this.handleModeChange.bind(this),
            disabled: true,
            style: { padding: "6px", fontSize: "14px", borderRadius: "4px", border: "1px solid #ccc" }
          },
            SurveyUI.createElement("option", { value: "generate" }, "Generate"),
            SurveyUI.createElement("option", { value: "edit" }, "Edit Existing")
          ),
          SurveyUI.createElement("button", {
            className: "sd-btn sd-btn--action",
            id: "send-prompt-button",
            style: { padding: "5px 12px", fontSize: "14px" },
            onClick: this.sendPrompt.bind(this),
            disabled: this.state.isLoading
          }, this.state.isLoading ? "Editing" : "Submit")
        ),

        // CKEditor text area
        SurveyUI.createElement("div", {
          id: "editor",
          value: this.state.prompt,
          onChange: this.handlePromptChange.bind(this),
          placeholder: "Enter your prompt here...",
          style: {
            width: "-webkit-fill-available",
            height: "100%",
            resize: "none",
            padding: "10px",
            fontSize: "14px",
            margin: "0 1.2rem"
          }
        })
      )
    );
  }
}

// Utility functions for CKEditor management
function saveEditorContent() {
  if (ckeditorInstance) {
    const content = ckeditorInstance.getData();
    localStorage.setItem(LOCALSTORAGE_KEY, content);
  }
}

function createOrRestoreEditor() {
  const editorElement = document.querySelector('#editor');
  if (!editorElement) return;

  // Destroy existing instance if it exists
  if (ckeditorInstance) {
    ckeditorInstance.destroy().then(() => {
      createNewEditor();
    }).catch(error => {
      console.error('Error destroying editor:', error);
      createNewEditor();
    });
  } else {
    createNewEditor();
  }
}

function createNewEditor() {
  const editorElement = document.querySelector('#editor');
  if (!editorElement) return;

  BalloonEditor.create(editorElement, editorConfigs)
    .then(editor => {
      ckeditorInstance = editor;
      
      // Restore content from localStorage
      const savedContent = localStorage.getItem(LOCALSTORAGE_KEY);
      if (savedContent) {
        editor.setData(savedContent);
      }
      
      // Save content whenever it changes
      editor.model.document.on('change:data', () => {
        saveEditorContent();
      });
    })
    .catch(error => {
      console.error('Error creating editor:', error);
    });
}

// retry to check element #editor until it exists
function waitForEditor(found = false) {
  const editorElement = document.getElementById("editor");
  
  if (editorElement && !found) {
    found = true;
    
    // register listener for #tab-ai-agent is clicked
    document.getElementById('tab-ai-agent').addEventListener('click', () => {
      setTimeout(() => {
        createOrRestoreEditor();
      }, 100); // Small delay to ensure DOM is ready
    });
    
    // Add listeners for other tabs to save content when switching away
    const allTabs = document.querySelectorAll('[id^="tab-"]:not(#tab-ai-agent)');
    allTabs.forEach(tab => {
      tab.addEventListener('click', () => {
        saveEditorContent();
      });
    });
    
    // Create editor initially
    createOrRestoreEditor();
  } else if (!found) {
    setTimeout(() => waitForEditor(found), 500);
  }
}

waitForEditor();