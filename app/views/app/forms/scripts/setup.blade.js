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