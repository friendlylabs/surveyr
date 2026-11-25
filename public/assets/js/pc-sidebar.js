/**
 * pc-sidebar.js — app sidebar: off-canvas drawer below lg, collapsible icon rail at lg+.
 * Markup contract:
 *   <button data-pc-trigger="sidebar" class="lg:hidden">...</button>                 (mobile open)
 *   <div data-pc-component="sidebar-backdrop" class="pc-sidebar-backdrop"></div>
 *   <aside data-pc-component="sidebar" class="pc-sidebar">...</aside>
 *   <button data-pc-trigger="sidebar-collapse" aria-expanded="true">...</button>      (desktop rail)
 *
 * The desktop collapsed state lives on <html data-pc-sidebar="collapsed"> so the brand cell
 * in the top bar and the sidebar resize together, and is remembered in localStorage.
 * Pages that need the room (wide editors, data grids) set <html data-pc-sidebar-auto="collapsed">: they always
 * open collapsed, and toggling there lasts for the visit without changing the saved preference.
 * Windows ≤1280px wide behave the same way (rail by default, toggle not saved); crossing that width
 * re-applies the default for the new size.
 * Put this in <head> to apply it before first paint:
 *   <script>(function(d){try{if(d.dataset.pcSidebarAuto==="collapsed"||matchMedia("(max-width: 1280px)").matches||localStorage.getItem("pc-sidebar")==="collapsed")d.dataset.pcSidebar="collapsed"}catch(e){}})(document.documentElement)</script>
 *
 * Labels for the collapse button: give it data-pc-label-expanded / data-pc-label-collapsed and
 * a child [data-pc-sidebar-label]; its data-pc-tooltip is kept in sync too.
 * JS API: pcSidebar.open() / close() / toggle() / collapse(bool) / isCollapsed()
 */
(function () {
  const KEY = "pc-sidebar";
  const root = document.documentElement;
  const narrow = window.matchMedia("(max-width: 1280px)");

  /** Auto-collapsed (page asks for it, or the window is narrow): toggles last only until the next reset. */
  function isAuto() {
    return root.hasAttribute("data-pc-sidebar-auto") || narrow.matches;
  }
  function savedCollapsed() {
    try {
      return localStorage.getItem(KEY) === "collapsed";
    } catch (e) {
      return false;
    }
  }

  function getSidebar() {
    return document.querySelector('[data-pc-component="sidebar"]');
  }
  function getBackdrop() {
    return document.querySelector('[data-pc-component="sidebar-backdrop"]');
  }

  function open() {
    const el = getSidebar();
    const bd = getBackdrop();
    if (!el || el.getAttribute("data-pc-open") === "true") return;
    el.setAttribute("data-pc-open", "true");
    if (bd) bd.setAttribute("data-pc-open", "true");
    pc.lockScroll(true);
  }

  function close() {
    const el = getSidebar();
    const bd = getBackdrop();
    if (!el || el.getAttribute("data-pc-open") !== "true") return;
    el.setAttribute("data-pc-open", "false");
    if (bd) bd.setAttribute("data-pc-open", "false");
    pc.lockScroll(false);
  }

  function toggle() {
    const el = getSidebar();
    el && el.getAttribute("data-pc-open") === "true" ? close() : open();
  }

  function isCollapsed() {
    return root.getAttribute("data-pc-sidebar") === "collapsed";
  }

  function syncCollapseButtons() {
    const collapsed = isCollapsed();
    pc.qsa('[data-pc-trigger="sidebar-collapse"]').forEach((btn) => {
      btn.setAttribute("aria-expanded", String(!collapsed));
      const label = collapsed ? btn.getAttribute("data-pc-label-collapsed") : btn.getAttribute("data-pc-label-expanded");
      if (!label) return;
      const text = btn.querySelector("[data-pc-sidebar-label]");
      if (text) text.textContent = label;
      btn.setAttribute("data-pc-tooltip", label);
    });
  }

  function collapse(on) {
    const next = on == null ? !isCollapsed() : !!on;
    if (next) root.setAttribute("data-pc-sidebar", "collapsed");
    else root.removeAttribute("data-pc-sidebar");
    if (!isAuto()) save(next);
    sync();
  }

  function save(collapsed) {
    try {
      localStorage.setItem(KEY, collapsed ? "collapsed" : "expanded");
    } catch (e) {}
  }

  function sync() {
    syncCollapseButtons();
    if (window.pcTooltip) pcTooltip.hide();
  }

  /** Applies the default for the current width without touching the saved preference. */
  function reset() {
    if (isAuto() || savedCollapsed()) root.setAttribute("data-pc-sidebar", "collapsed");
    else root.removeAttribute("data-pc-sidebar");
    sync();
  }

  window.pcSidebar = { open, close, toggle, collapse, isCollapsed };

  document.addEventListener("click", (e) => {
    if (e.target.closest('[data-pc-trigger="sidebar"]')) toggle();
    if (e.target.closest('[data-pc-trigger="sidebar-collapse"]')) collapse();
    if (e.target.closest('[data-pc-component="sidebar-backdrop"]')) close();
    if (e.target.closest('[data-pc-component="sidebar"] a.pc-nav-item')) close();
  });

  pc.onEscape(() => close());
  // A drawer left open on a small screen shouldn't keep the page locked after widening.
  window.matchMedia("(min-width: 1024px)").addEventListener("change", (mq) => mq.matches && close());
  narrow.addEventListener("change", reset);

  if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", syncCollapseButtons);
  else syncCollapseButtons();
})();
