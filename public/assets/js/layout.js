var navbarTopShape = window.config.config.phoenixNavbarTopShape;
var navbarPosition = window.config.config.phoenixNavbarPosition;
var body = document.querySelector('body');
var navbarDefault = document.querySelector('#navbarDefault');
var navbarTop = document.querySelector('#navbarTop');
var topNavSlim = document.querySelector('#topNavSlim');
var navbarTopSlim = document.querySelector('#navbarTopSlim');
var navbarCombo = document.querySelector('#navbarCombo');
var navbarComboSlim = document.querySelector('#navbarComboSlim');
var dualNav = document.querySelector('#dualNav');

var documentElement = document.documentElement;
var navbarVertical = document.querySelector('.navbar-vertical');

if (navbarTopShape === 'default' && navbarPosition === 'horizontal') {
    navbarDefault?.remove();
    topNavSlim?.remove();
    navbarVertical?.remove();
    navbarTopSlim?.remove();
    navbarCombo?.remove();
    navbarComboSlim?.remove();
    dualNav?.remove();
    navbarTop.removeAttribute('style');
    document.documentElement.setAttribute('data-navigation-type', 'horizontal');
}

var navbarTopStyle = window.config.config.phoenixNavbarTopStyle;
var navbarTop = document.querySelector('.navbar-top');
if (navbarTopStyle === 'darker') {
    navbarTop.setAttribute('data-navbar-appearance', 'darker');
}

var navbarVerticalStyle = window.config.config.phoenixNavbarVerticalStyle;
var navbarVertical = document.querySelector('.navbar-vertical');
if (navbarVerticalStyle === 'darker') {
    navbarVertical.setAttribute('data-navbar-appearance', 'darker');
}

// add active to current page
try {
    var currentUrl = window.location.href;
    var navLinks = document.querySelectorAll('.navbar-nav a');

    navLinks.forEach(function (navLink) {
        if (navLink.href === currentUrl) {
            navLink.classList.add('active');

            // Check if navLink is in a dropdown
            var dropdown = navLink.closest('.dropdown');
            if (dropdown) {
                var dropdownToggle = dropdown.querySelector('.dropdown-toggle');
                if (dropdownToggle) {
                    dropdownToggle.classList.add('active');
                } else {
                    console.warn('Dropdown found, but no .dropdown-toggle element exists.');
                }
            }
        }
    });
} catch (error) {
    console.error('An error occurred while processing navigation links:', error);
}


function menuToOffcanvas() {
    // check if offCanvas #mobileMenu exists, if not create it
    var offCanvas = document.querySelector('#mobileMenu');
    var sideBarContent = document.getElementById('navbarTopCollapse')

    if (!offCanvas) {
        var offCanvas = document.createElement('div');
        offCanvas.id = 'mobileMenu';
        offCanvas.classList.add('offcanvas', 'offcanvas-start');
        offCanvas.style.maxWidth = '320px';
        offCanvas.innerHTML = `
            <div class="offcanvas-header border-bottom" style="height: 65px;">
                <img id="mobileMenuLogo" src="/assets/images/brand/logo-dark.png" alt="logo" height="40" />
                <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body navbar-mobile">
                ${sideBarContent.innerHTML}
            </div>
        `;
        document.body.appendChild(offCanvas);
        console.log('offCanvas created');

        //menuToOffcanvas();
        setTimeout(() => { menuToOffcanvas(); }, 100);
    }else{

        var appTheme = localStorage.getItem('phoenixTheme');
        var logo = document.getElementById('mobileMenuLogo');
        if (appTheme === 'dark') {
            logo.src = '/assets/images/brand/logo-light.png';
        } else {
            logo.src = '/assets/images/brand/logo-dark.png';
        }

        var offCanvas = document.getElementById('mobileMenu');
        var mobileMenuCanvas = new bootstrap.Offcanvas(offCanvas);

        // check if offCanvas has .show class
        if (offCanvas.classList.contains('show')) {

            if(bootstrap.Offcanvas.getInstance(offCanvas).isShown){
                mobileMenuCanvas.hide();
            }else{
                // overide the default offcanvas hide method
                offCanvas.classList.remove('show');
                var backdrop = document.querySelector('.offcanvas-backdrop');
                backdrop.remove();
            }

        } else {
            mobileMenuCanvas.show();
        }
    }

}