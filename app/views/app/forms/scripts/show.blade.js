const surveyMode = `{{ $surveyMode }}`;
const surveyJson = `{!! json_encode($formContent) !!}`;
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

// Add event listener to handle survey completion
survey.onComplete.add(function (result) {

    const submissionUrl = `{{ route('forms.collect', md5($form->id), $form->slug) }}`;
    const submissionMethod = 'POST';

    $.ajax({
        url: submissionUrl,
        method: submissionMethod,
        headers: {
            'X-CSRF-TOKEN': csrf_token
        },
        data: {
            content: result.data
        },
        success: function (response) {
            if (response.status) {
                toast.success({ message: response.message });
            } else {
                toast.error({ message: response.message ?? 'An Unknown error occured' });
            }
        },
        error: function (error) {
            toast.error({ message: 'An error occurred while submitting the form' });
        }
    });
            
});

// initializers and settings
const toast = iziToast;
toast.settings({
    timeout: 1500,
    transitionIn: 'fadeInDown',
    transitionOut: 'fadeOutUp',
    position: 'bottomCenter',
    close: true,
    progressBar: true,
    pauseOnHover: true
});

document.addEventListener("DOMContentLoaded", function() {
    survey.render(document.getElementById("surveyContainer"));
});
