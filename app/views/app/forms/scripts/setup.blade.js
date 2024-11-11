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