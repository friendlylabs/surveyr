/*
 * Layout glue for the vertical (sidebar) navigation.
 *
 * Phoenix handles the desktop sidebar itself: `.navbar-vertical` is fixed
 * beside `.content`, and `.navbar-vertical-toggle` flips the html class
 * `navbar-vertical-collapsed` (persisted as phoenixIsNavbarVerticalCollapsed).
 * What lives here is the bits Phoenix does not do for us: the mobile
 * off-canvas menu, the ⌘K search hotkey and a resize nudge on collapse.
 */

var navbarTopStyle = window.config.config.phoenixNavbarTopStyle;
var navbarTop = document.querySelector('.navbar-top');
if (navbarTopStyle === 'darker' && navbarTop) {
    navbarTop.setAttribute('data-navbar-appearance', 'darker');
}

var navbarVerticalStyle = window.config.config.phoenixNavbarVerticalStyle;
var navbarVertical = document.querySelector('.navbar-vertical');
if (navbarVerticalStyle === 'darker' && navbarVertical) {
    navbarVertical.setAttribute('data-navbar-appearance', 'darker');
}

// pages that start collapsed (data-nav-collapsed="page", e.g. the zone editor)
// keep the toggle local to that page: Phoenix's own handler would persist the
// flip as the user's global preference, so it is bypassed here
if (document.documentElement.dataset.navCollapsed === 'page') {
    document.addEventListener('click', function (event) {
        var toggle = event.target.closest('.navbar-vertical-toggle');
        if (!toggle) return;

        event.stopPropagation();
        toggle.blur();
        document.documentElement.classList.toggle('navbar-vertical-collapsed');
        toggle.dispatchEvent(new CustomEvent('navbar.vertical.toggle'));
    }, true);
}

// add active to current page (blade's @active covers section prefixes; this
// catches exact matches for links that live outside those sections)
try {
    var currentUrl = window.location.href.split('#')[0];
    var navLinks = document.querySelectorAll('.navbar-vertical a.nav-link');

    navLinks.forEach(function (navLink) {
        if (navLink.href === currentUrl) {
            navLink.classList.add('active');
        }
    });
} catch (error) {
    console.error('An error occurred while processing navigation links:', error);
}

/**
 * Mobile navigation: clones the sidebar's nav list (plus the Settings link
 * from its footer) into a Bootstrap offcanvas the first time it is opened,
 * then toggles it on subsequent taps.
 */
function menuToOffcanvas() {
    var offCanvas = document.getElementById('mobileMenu');

    if (!offCanvas) {
        var navList = document.getElementById('navbarVerticalNav');
        var footerLink = document.querySelector('.navbar-vertical-footer .navbar-vertical-footer-link');

        offCanvas = document.createElement('div');
        offCanvas.id = 'mobileMenu';
        offCanvas.classList.add('offcanvas', 'offcanvas-start', 'navbar-vertical-offcanvas');
        offCanvas.setAttribute('tabindex', '-1');
        offCanvas.innerHTML = `
            <div class="offcanvas-header border-bottom">
                <img id="mobileMenuLogo" src="/assets/images/brand/logo-dark.png" alt="logo" height="34" />
                <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body navbar-mobile p-0">
                <div class="navbar-vertical-content">
                    <ul class="navbar-nav flex-column">${navList ? navList.innerHTML : ''}</ul>
                </div>
                ${footerLink ? `
                    <div class="navbar-mobile-footer border-top">
                        ${footerLink.outerHTML}
                    </div>` : ''}
            </div>
        `;

        // tooltips only make sense on the collapsed desktop rail
        offCanvas.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
            el.removeAttribute('data-bs-toggle');
            el.removeAttribute('data-bs-title');
        });

        document.body.appendChild(offCanvas);
    }

    var logo = document.getElementById('mobileMenuLogo');
    if (logo) {
        logo.src = localStorage.getItem('phoenixTheme') === 'dark'
            ? '/assets/images/brand/logo-light.png'
            : '/assets/images/brand/logo-dark.png';
    }

    bootstrap.Offcanvas.getOrCreateInstance(offCanvas).toggle();
}

document.addEventListener('DOMContentLoaded', function () {

    // content width changes when the sidebar collapses/expands; let
    // viewport-aware widgets (spreadsheet, leaflet, charts) re-measure
    var verticalToggle = document.querySelector('.navbar-vertical-toggle');
    if (verticalToggle) {
        verticalToggle.addEventListener('navbar.vertical.toggle', function () {
            setTimeout(function () { window.dispatchEvent(new Event('resize')); }, 250);
        });
    }

    // ⌘K / Ctrl+K opens the global search
    document.addEventListener('keydown', function (e) {
        if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
            var searchModal = document.getElementById('searchBoxModal');
            if (!searchModal) return;
            e.preventDefault();
            bootstrap.Modal.getOrCreateInstance(searchModal).show();
        }
    });

    // focus the search input once the modal is visible
    var searchModal = document.getElementById('searchBoxModal');
    if (searchModal) {
        searchModal.addEventListener('shown.bs.modal', function () {
            var input = searchModal.querySelector('#globalSearch');
            if (input) input.focus();
        });
    }
});
