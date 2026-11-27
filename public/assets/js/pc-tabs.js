/**
 * pc-tabs.js — tab / segmented-control switcher (page tabs, view switch, range picker).
 * Markup contract:
 *   <div data-pc-tabs-group class="pc-tabs" role="tablist">
 *     <button data-pc-trigger="tabs" data-pc-value="active" class="pc-tab pc-tab-active" role="tab" aria-selected="true">Active</button>
 *     <button data-pc-trigger="tabs" data-pc-value="archived" class="pc-tab" role="tab" aria-selected="false">Archived</button>
 *   </div>
 * Visual variants: .pc-tabs (dark pill), .pc-segmented (joined buttons), .pc-tabs-underline.
 *
 * Fires a `pc:tabchange` CustomEvent (detail: { value }) on the group element — pages listen for it
 * to re-render whatever the tab controls.
 * Optional panels: give the group data-pc-tabs-panels="#container" and each child panel
 * data-pc-tab-panel="<value>"; the matching panel gets data-pc-open="true", the rest "false".
 * Optional url: data-pc-tabs-query="tab" keeps ?tab=<value> in step with the open tab, so a reload (e.g. after a
 * save) or a shared link comes back to it; the server opens that tab on render.
 * Left/Right arrow keys move between tabs of a focused group.
 * JS API: pcTabs.activate(groupEl, value)
 */
(function () {
  function triggers(group) {
    return pc.qsa('[data-pc-trigger="tabs"]', group).filter((b) => b.closest("[data-pc-tabs-group]") === group);
  }

  function activate(group, value) {
    if (!group) return;
    triggers(group).forEach((btn) => {
      const active = btn.getAttribute("data-pc-value") === value;
      btn.classList.toggle("pc-tab-active", active);
      btn.setAttribute("aria-selected", String(active));
      btn.setAttribute("tabindex", active ? "0" : "-1");
    });
    const panelsSel = group.getAttribute("data-pc-tabs-panels");
    const container = panelsSel && document.querySelector(panelsSel);
    if (container) {
      pc.qsa("[data-pc-tab-panel]", container).forEach((panel) => {
        panel.setAttribute("data-pc-open", String(panel.getAttribute("data-pc-tab-panel") === value));
      });
    }
  }

  function select(trigger) {
    const group = trigger.closest("[data-pc-tabs-group]");
    if (!group) return;
    const value = trigger.getAttribute("data-pc-value");
    activate(group, value);
    const key = group.getAttribute("data-pc-tabs-query");
    if (key) {
      const url = new URL(location.href);
      url.searchParams.set(key, value);
      history.replaceState(history.state, "", url);
    }
    group.dispatchEvent(new CustomEvent("pc:tabchange", { detail: { value }, bubbles: true }));
  }

  window.pcTabs = { activate };

  document.addEventListener("click", (e) => {
    const trigger = e.target.closest('[data-pc-trigger="tabs"]');
    if (trigger) select(trigger);
  });

  document.addEventListener("keydown", (e) => {
    if (e.key !== "ArrowLeft" && e.key !== "ArrowRight") return;
    const trigger = e.target.closest('[data-pc-trigger="tabs"]');
    if (!trigger) return;
    const list = triggers(trigger.closest("[data-pc-tabs-group]"));
    const i = list.indexOf(trigger);
    // the tab row mirrors on RTL pages, so Left moves to the next tab there
    const forward = (e.key === "ArrowRight") !== pc.rtl();
    const next = list[(i + (forward ? 1 : -1) + list.length) % list.length];
    e.preventDefault();
    next.focus();
    select(next);
  });
})();
