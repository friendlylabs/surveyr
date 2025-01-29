/**
 * This script is used to render the survey form and handle submission.
 * TODO: The script requires optimization and refactoring.
 *       - webhook handling is to be done on the back as a queued job
 */

const surveyMode = `{{ $surveyMode }}`;
const surveyJson = `{!! json_encode($formContent) !!}`;

var formTheme = "{{ $form->theme }}";
const SurveyThemeLight = SurveyTheme[formTheme + 'LightPanelless'];

const survey = new Survey.Model(surveyJson);

// Check if survey is in restricted mode
if (surveyMode === 'restricted') {
    const banner = document.createElement('div');
    banner.innerText = "This form is restricted or is no longer receiving responses";
    banner.style.cssText = `
        padding: 30px 10px;
        background-color: #ffcccc;
        color: #b00;
        text-align: center;
        font-weight: bold;
        font-size: 1.2em;
        position: fixed;
        width: 100%;
        bottom: 0;  
        left: 0;
        z-index: 1;
    `;
    document.body.insertBefore(banner, document.body.firstChild); // Adds banner at the top of the page
}

// Function to send AJAX requests
function sendAjaxRequest(url, data, includeCsrf = false) {

    /**
     * TODO: CSRF token handling
     * Observe more why the CSRF token is not accepted when
     * sent through the headers.
     */
    // const headers = includeCsrf ? { 'X-CSRF-TOKEN': csrf_token } : {};    
    const token = includeCsrf ? csrf_token : null;
    
    return $.ajax({
        url: url,
        method: 'POST',
        // headers: headers,
        data: { 
            _token: token,
            content: data
        }
    });
}

// Add event listener to handle survey completion
survey.onComplete.add(function (result) {

    const submissionUrl = `{{ route('forms.collect', md5($form->id), $form->slug) }}`;
    const webhook = `{{ $form->webhook_url ?? null }}`;

    // Prepare requests
    const requests = [
        // Send data to your main server (with CSRF token)
        sendAjaxRequest(submissionUrl, result.data, true)
    ];

    // If webhook URL is defined, send data to it (without CSRF token)
    if (webhook) {
        requests.push(sendAjaxRequest(webhook, result.data, false));
    }

    // Use Promise.all to handle both requests
    Promise.all(requests)
        .then(([mainResponse, webhookResponse]) => {
            // Handle response from your main server
            if (mainResponse.status) {
                toast.success({ message: mainResponse.message });
                document.querySelector('.sd-completedpage').innerHTML = `<h3>Thank you for completing the Form 🎉</h3>`;
            } else {
                toast.error({ message: mainResponse.message ?? 'An unknown error occurred' });
            }

            // Optionally handle webhook response if needed
            if (webhook && webhookResponse) {
                console.log('Webhook Response:', webhookResponse);
            }
        })
        .catch(() => {
            toast.error({ message: 'An error occurred while submitting the form' });
        });
});

// Initialize and render survey when DOM is ready
document.addEventListener("DOMContentLoaded", function() {
    survey.render(document.getElementById("surveyContainer"));
    survey.applyTheme(SurveyThemeLight);
});