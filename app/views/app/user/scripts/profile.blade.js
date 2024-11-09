// switch profile overview tabs
$('.profile-overview-switch').click(function() {

    var targetBlock = $(this).data('target-block');
    $('.profile-overview-menu').removeClass('active');
    $(this).addClass('active');

    $('.profile-overview-tab').addClass('d-none');
    $('#profileDetails, #securityDetails').addClass('d-none');
    $(targetBlock).removeClass('d-none');

});

// update profile password
function updatePassword(event) {
    event.preventDefault();
    var form = $('#passwordForm');

    // validate form
    if (form.find('input[name="new_password"]').val() != form.find('input[name="confirm_password"]').val()) {
        toast.error({ message: 'New Password and Confirm Password do not match' });
        return;
    }

    // password length
    if (form.find('input[name="new_password"]').val().length < 6) {
        toast.error({ message: 'Password must be at least 6 characters' });
        return;
    }

    buttonState('#btnUpdatePassword', 'loading');

    $.ajax({
        url: form.attr('action'),
        method: 'POST',
        data: form.serialize(),
        success: function(response) {
            if (response.status) {
                toast.success({ message: response.message });
            } else {
                toast.error({ message: response.message });
            }
        },
        error: function() {
            toast.error({ message: 'Unknown Error Occurred' });
        },
        complete: function() {
            buttonState('#btnUpdatePassword', 'reset', 'Update Password');
        }
    });
}