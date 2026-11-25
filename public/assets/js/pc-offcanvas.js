/**
 * pc-offcanvas.js — slide-in side panel (record details, profile, filters).
 * Markup contract:
 *   <div id="detailDrawer" class="pc-offcanvas" role="dialog" aria-modal="true" aria-hidden="true">
 *     <div class="pc-offcanvas-backdrop" data-pc-dismiss="offcanvas"></div>
 *     <div class="pc-offcanvas-panel">
 *       <div class="pc-offcanvas-header">
 *         <div class="pc-offcanvas-tools">
 *           <button data-pc-offcanvas-expand aria-pressed="false">...</button>   (optional: widens the panel)
 *           <button data-pc-dismiss="offcanvas">...</button>
 *         </div>
 *         <div class="pc-offcanvas-title">...</div>
 *       </div>
 *       <div class="pc-offcanvas-body">...</div>
 *       <div class="pc-offcanvas-footer">...</div>
 *     </div>
 *   </div>
 * Variants: pc-offcanvas-start (slides in from the left), pc-offcanvas-lg (wider panel).
 * Trigger: <button data-pc-trigger="offcanvas" data-pc-target="#detailDrawer">Open</button>
 * Expanded state lives on the root as data-pc-expanded="true" and resets each time the panel closes.
 * JS API: pcOffcanvas.open('detailDrawer') / close('detailDrawer') / toggle('detailDrawer') / expand(id, on?) / isOpen(id)
 * Events: `pc:open` / `pc:close` on the root element.
 */
(function () {
  const registry = new Map();

  function getEl(id) {
    return document.getElementById(id);
  }

  function isOpen(id) {
    const el = getEl(id);
    return !!el && el.getAttribute("data-pc-open") === "true";
  }

  function open(id) {
    const el = getEl(id);
    if (!el || isOpen(id)) return;
    const lastFocused = document.activeElement;
    el.setAttribute("data-pc-open", "true");
    el.setAttribute("aria-hidden", "false");
    pc.lockScroll(true);
    const panel = el.querySelector(".pc-offcanvas-panel") || el;
    pc.focusFirst(panel);
    const untrap = pc.trapFocus(panel);
    const unesc = pc.onEscape(() => close(id));
    registry.set(id, { lastFocused, untrap, unesc });
    el.dispatchEvent(new CustomEvent("pc:open"));
  }

  function close(id) {
    const el = getEl(id);
    if (!el || !isOpen(id)) return;
    el.setAttribute("data-pc-open", "false");
    el.setAttribute("aria-hidden", "true");
    expand(id, false);
    pc.lockScroll(false);
    const reg = registry.get(id);
    if (reg) {
      reg.untrap && reg.untrap();
      reg.unesc && reg.unesc();
      if (reg.lastFocused && typeof reg.lastFocused.focus === "function") {
        reg.lastFocused.focus();
      }
      registry.delete(id);
    }
    el.dispatchEvent(new CustomEvent("pc:close"));
  }

  function toggle(id) {
    isOpen(id) ? close(id) : open(id);
  }

  /** Widens the panel (on), back to its size (off), or flips it; keeps the expand buttons' aria-pressed in step. */
  function expand(id, on) {
    const el = getEl(id);
    if (!el) return;
    const next = on == null ? el.getAttribute("data-pc-expanded") !== "true" : !!on;
    if (next) el.setAttribute("data-pc-expanded", "true");
    else el.removeAttribute("data-pc-expanded");
    el.querySelectorAll("[data-pc-offcanvas-expand]").forEach((btn) => btn.setAttribute("aria-pressed", String(next)));
  }

  window.pcOffcanvas = { open, close, toggle, expand, isOpen };

  document.addEventListener("click", (e) => {
    const trigger = e.target.closest('[data-pc-trigger="offcanvas"]');
    if (trigger) {
      const id = pc.idFromTarget(trigger.getAttribute("data-pc-target"));
      if (id) {
        e.preventDefault();
        toggle(id);
      }
      return;
    }
    const expander = e.target.closest("[data-pc-offcanvas-expand]");
    if (expander) {
      const host = expander.closest(".pc-offcanvas");
      if (host) expand(host.id);
      return;
    }
    const dismiss = e.target.closest('[data-pc-dismiss="offcanvas"]');
    if (dismiss) {
      const host = dismiss.closest(".pc-offcanvas");
      if (host) close(host.id);
    }
  });
})();
