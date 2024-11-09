$(document).ready(function() {
    var resendTimer = $('#resendTimer');
    var resendTime = resendTimer.data('resend-time');
    var resendInterval = setInterval(function() {
        resendTime--;
        resendTimer.text('Resend Code (' + resendTime + ')');
        if (resendTime <= 0) {
            resendTimer.text('Resend Code').removeAttr('disabled');
            clearInterval(resendInterval);
        }
    }, 1000);
});

$('#resendTimer').click(function() {
    buttonState('#resendTimer', 'loading');
    $.get("{{ route('2fa.resend') }}", function(response) {
        if (response.status) {
            $('#resendTimer').attr('disabled', 'disabled').text('Resend Code (' + response.time + ')');
            var resendTime = response.timer;
            var resendInterval = setInterval(function() {
                resendTime--;
                $('#resendTimer').text('Resend Code (' + resendTime + ')');
                if (resendTime <= 0) {
                    $('#resendTimer').text('Resend Code').removeAttr('disabled');
                    clearInterval(resendInterval);
                }
            }, 1000);
        }else{
            toast.error(response.message);
            buttonState('#resendTimer', 'reset', 'Resend Code');
        }
    });
});