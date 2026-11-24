/**
 * pc-modal.js — modal dialogs.
 * Markup contract:
 *   <div id="exampleModal" class="pc-modal" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="exampleModalTitle">
 *     <div class="pc-modal-backdrop" data-pc-dismiss="modal"></div>
 *     <div class="pc-modal-panel">
 *       <div class="pc-modal-header"><div id="exampleModalTitle" class="pc-modal-title">Title</div>
 *         <button class="pc-close" data-pc-dismiss="modal" aria-label="Close">×</button></div>
 *       <div class="pc-modal-body">...</div>
 *       <div class="pc-modal-footer">...</div>
 *     </div>
 *   </div>
 * Sizes: add pc-modal-sm / pc-modal-lg / pc-modal-xl to the root.
 * Static backdrop: add data-pc-static to the root — backdrop clicks and Escape are ignored.
 * Trigger: <button data-pc-trigger="modal" data-pc-target="#exampleModal">Open</button>
 * JS API: pcModal.open('exampleModal') / close('exampleModal') / toggle('exampleModal') / isOpen(id)
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
    const panel = el.querySelector(".pc-modal-panel") || el;
    pc.focusFirst(panel);
    const untrap = pc.trapFocus(panel);
    const unesc = pc.onEscape(() => {
      if (!el.hasAttribute("data-pc-static")) close(id);
    });
    registry.set(id, { lastFocused, untrap, unesc });
    el.dispatchEvent(new CustomEvent("pc:open"));
  }

  function close(id) {
    const el = getEl(id);
    if (!el || !isOpen(id)) return;
    el.setAttribute("data-pc-open", "false");
    el.setAttribute("aria-hidden", "true");
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

  window.pcModal = { open, close, toggle, isOpen };

  document.addEventListener("click", (e) => {
    const trigger = e.target.closest('[data-pc-trigger="modal"]');
    if (trigger) {
      const id = pc.idFromTarget(trigger.getAttribute("data-pc-target"));
      if (id) {
        e.preventDefault();
        toggle(id);
      }
      return;
    }
    const dismiss = e.target.closest('[data-pc-dismiss="modal"]');
    if (dismiss) {
      const host = dismiss.closest(".pc-modal");
      if (!host) return;
      const isBackdrop = dismiss.classList.contains("pc-modal-backdrop");
      if (isBackdrop && host.hasAttribute("data-pc-static")) return;
      close(host.id);
    }
  });
})();
