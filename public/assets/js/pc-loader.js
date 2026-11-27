/**
 * pc-loader.js — the full-page loader (<x-page-loader />).
 * The layout renders it in data-pc-state="loading", so it's up from the page's first paint. This file finishes it once
 * the page has fully loaded (images, fonts and scripts included), and fades it back in over the page the moment the
 * user leaves for another: a same-tab link to this site, or a form that submits the normal (non-AJAX) way.
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

  /** Fade the loader in over the current page, the line starting over empty. */
  function show() {
    loader.getAnimations().forEach((animation) => animation.cancel());
    loader.dataset.pcState = "done";
    // data-pc-restart drops every scene animation for one reflow, so the line starts over empty (app.css warm-up)
    // and the progress restarts from zero
    loader.dataset.pcRestart = "";
    void loader.offsetWidth;
    delete loader.dataset.pcRestart;
    loader.dataset.pcState = "loading";
    loader.animate([{ opacity: 0 }, { opacity: 1 }], { duration: 150, easing: "ease-out" });
  }

  /** Fill the progress bar and fade out. */
  function hide() {
    if (loader.dataset.pcState === "done") return;
    const opacity = Number(getComputedStyle(loader).opacity);
    loader.dataset.pcState = "done";
    if (opacity > 0) {
      loader.animate([{ visibility: "visible", opacity }, { visibility: "visible", opacity: 0 }], {
        duration: 260,
        // the delay lets the progress bar finish; "backwards" holds the loader up through it instead of blinking out
        delay: 160,
        easing: "ease",
        fill: "backwards",
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

  if (document.readyState === "complete") hide();
  else window.addEventListener("load", hide, { once: true });

  window.pcPageLoader = { show, hide };
})();
