const publicUrl = document.getElementById('publicUrl');
var qrcode = new QRCode(document.getElementById("qrcode"), {
    width : 150,
    height : 150,    
    colorDark : "#1e6b5b",
    colorLight : "#ffffff",
});

qrcode.makeCode(publicUrl.value);

document.querySelectorAll('.profile-overview-switch').forEach(button => {
    button.addEventListener('click', function() {
        const targetBlock = this.getAttribute('data-target-block');
        
        // Remove 'active' class from all menu items
        document.querySelectorAll('.profile-overview-menu').forEach(menu => menu.classList.remove('active'));
        this.classList.add('active');
        
        // Hide all tabs
        document.querySelectorAll('.setup-card').forEach(tab => tab.classList.add('d-none'));
        document.querySelector(targetBlock).classList.remove('d-none');
    });
});

function downloadQRCode(name) {
    const qrcodeDiv = document.getElementById('qrcode');
    const img = qrcodeDiv.querySelector('img');

    if (img && img.src) {
        const link = document.createElement('a');
        link.href = img.src;
        link.download = `${name}-qrcode.png`; // Set the filename with the user's name
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link); // Cleanup
    } else {
        alert('No image found to download.');
    }
}

document.addEventListener("DOMContentLoaded", function() {
    $('.theme-card').on('click', function () {
        const $this = $(this);
        const selectedTheme = $this.data('theme');
        
        // Remove 'bg-danger' from all cards and add it to the selected card
        $('.theme-card').removeClass('bg-danger');
        $this.addClass('bg-danger');

        $.ajax({
            url: "{{ route('forms.setup.update', 'theme', $form->id) }}",
            type: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                theme: selectedTheme
            },
            success: function (response) {
                if (response.status) {
                    toast.success({message: response.message});
                } else {
                    toast.error({message: response.message ?? 'An error occurred'});
                }
            },
            error: function (xhr, status, error) {
                toast.error({message: 'An error occurred'});
            }
        });
    });
});