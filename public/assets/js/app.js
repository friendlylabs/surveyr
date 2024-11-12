// Event Listeners
$('.togglePassword').click(function() {
    $(this).toggleClass('ph-eye').toggleClass('ph-eye-slash');
    var input = $($(this).prev('input'));
    if (input.attr('type') === 'password') {
        input.attr('type', 'text');
    } else {
        input.attr('type', 'password');
    }
});

// Global Functions
function buttonState(button, state, initialText=null) {

    button = $(button);

    if (state === 'loading') {
        button.attr('disabled', true);
        button.html('<i class="fas fa-spinner fa-spin"></i>');
    } else {
        button.attr('disabled', false);
        button.html(initialText);
    }
}


function underDevelopment(){
    // prevent default action
    event.preventDefault();

    Swal.fire({
        title: 'Under Development',
        text: 'This feature is under development and will be available soon.',
        icon: 'info',
        confirmButtonText: 'OK'
    });
}


// Styles and Scripts Injection
function injectStylesheet(url) {
    var link = document.createElement('link');
    link.rel = 'stylesheet';
    link.type = 'text/css';
    link.href = url;
    document.head.appendChild(link);
}

function injectScript(url) {
    return new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = url;
        script.onload = resolve;
        script.onerror = reject;
        document.head.appendChild(script);
    });
}

function copyToClipboard(text) {
    var input = document.createElement('input');
    input.value = text;
    document.body.appendChild(input);
    input.select();
    document.execCommand('copy');
    document.body.removeChild(input);

    toast.info({ message: 'Copied to clipboard' });
}

function submitForm(event, responseHandler = null){
    event.preventDefault();

    const form = $(event.target); // Get the form element
    const isMultipart = form.attr('enctype') === 'multipart/form-data'; // Check if it's a multipart form

    // Create a FormData object if multipart, else serialize the form
    let formData;
    if (isMultipart) {
        formData = new FormData(event.target);
    } else {
        formData = form.serialize();
    }

    // find the submit button
    const submitButton = form.find('button[type="submit"]');
    const buttonLabel = submitButton.html() ?? null;
    
    if (submitButton) {
        buttonState(submitButton, 'loading');
    }

    $.ajax({
        url: form.attr('action'),
        method: form.attr('method') ?? 'POST',
        data: formData,
        processData: !isMultipart, // Don't process the data if it's multipart (FormData handles that)
        contentType: isMultipart ? false : 'application/x-www-form-urlencoded; charset=UTF-8', // Set content type accordingly
        success: function(response) {
            if (responseHandler && typeof responseHandler === 'function') {
                responseHandler(response);
            }else{
                if (response.status) {
                    toast.success({ message: response.message });
                }else{
                    toast.error({ message: response.message ?? 'An Unknown error occured' });
                }

                if(response.redirect){
                    setTimeout(() => {
                        window.location.href = response.redirect;
                    }, 1000);
                }
            }
        },
        error: function() {
            toast.error({ message: 'Unknown Error Occurred' });
        },
        complete: function() {
            // Reset button state if buttonId is provided
            if (submitButton) {
                buttonState(submitButton, 'reset', buttonLabel);
            }
        }
    });
}

// anchor tag click event
function confirmDelete(event){
    event.preventDefault();
    const buttonHref = event.target.href;
    var text = event.target.getAttribute('data-delete-msg') ?? 'You will not be able to recover this record!';

    Swal.fire({
        title: 'Are you sure?',
        text: text,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'No, cancel!',
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = buttonHref;
        }
    });
}

document.addEventListener('DOMContentLoaded', function () {

    // flatpickr datepicker
    if(document.querySelector('.date-selector')) {
        document.querySelectorAll('.date-selector').forEach(function(input) {
            flatpickr(input, {
                dateFormat: "Y-m-d", // Match format with server-side value
                defaultDate: input.value // Set each input's initial value as defaultDate
            });
        });
    }

    // bootstrap select
    if(document.querySelector('.form-selector')) {
        $('.form-selector').selectpicker();
    }

    // listent to all .fs-* classes and assign font-size dynamically
    $('[class*="fs-"]').each(function() {
        var classes = $(this).attr('class').split(' ');
        var fontSize = classes.find(c => c.startsWith('fs-'));
        var size = fontSize.split('-')[1];
        $(this).css('font-size', size + 'px');
    });

});