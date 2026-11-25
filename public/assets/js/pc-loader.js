/**
 * pc-loader.js — the full-page loader (<x-page-loader />).
 * The layout renders it in data-pc-state="loading"; app.css only fades it in after a short delay, so a page that's
 * ready quickly never shows it. This file finishes it once the page is ready, and starts it again when the user
 * leaves for another page: a same-tab link to this site, or a form that submits the normal (non-AJAX) way.
 * Markup contract:
 *   <div class="pc-loader pc-page-loader" data-pc-component="page-loader" data-pc-state="loading|done">
 *     ... <div class="pc-loader-progress"><span></span></div> ...
 *   </div>
 * Links opt out with data-pc-no-loader (downloads, links that open a file).
 * JS API: pcPageLoader.show() / hide()
 */
(function () {
  const loader = document.querySelector('[data-pc-component="page-loader"]');
  if (!loader) return;

  /** Start the loader again; a navigation that finishes inside the fade-in delay still never shows it. */
  function show() {
    loader.getAnimations().forEach((animation) => animation.cancel());
    loader.dataset.pcState = "done";
    // reflow so the fade-in delay and progress restart from zero
    void loader.offsetWidth;
    loader.dataset.pcState = "loading";
  }

  /** Fill the progress bar and fade out; skipped straight to hidden if it never became visible. */
  function hide() {
    if (loader.dataset.pcState === "done") return;
    const opacity = Number(getComputedStyle(loader).opacity);
    loader.dataset.pcState = "done";
    if (opacity > 0) {
      loader.animate([{ visibility: "visible", opacity }, { visibility: "visible", opacity: 0 }], {
        duration: 260,
        delay: 160,
        easing: "ease",
      });
    }
  }

  /** True when a click on `link` is an ordinary same-tab navigation to another page of this site. */
  function isPageNavigation(event, link) {
    if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
      return false;
    }
    if (link.hasAttribute("download") || link.hasAttribute("data-pc-no-loader")) return false;
    if (link.target && link.target !== "_self") return false;
    const url = new URL(link.href, location.href);
    if (url.origin !== location.origin) return false;
    // same page, different #hash: no load happens
    return !(url.pathname === location.pathname && url.search === location.search && url.hash);
  }

  // decided after every other handler has run: confirms, dropdowns and AJAX forms (submitForm) cancel the default
  document.addEventListener("click", (event) => {
    const link = event.target.closest("a[href]");
    if (link) setTimeout(() => isPageNavigation(event, link) && show());
  });

  document.addEventListener("submit", (event) => {
    const form = event.target;
    if ((form.target && form.target !== "_self") || form.hasAttribute("data-pc-no-loader")) return;
    setTimeout(() => event.defaultPrevented || show());
  });

  // back / forward restores the page from cache with the loader still showing
  window.addEventListener("pageshow", (event) => {
    if (event.persisted) hide();
  });

  if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", hide, { once: true });
  else hide();

  window.pcPageLoader = { show, hide };
})();
