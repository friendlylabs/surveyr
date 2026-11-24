/**
 * pc-popover.js — rich floating panels anchored to a trigger (info cards, inline help).
 * Markup contract:
 *   <button data-pc-trigger="popover" data-pc-target="#flag-3-q2" data-pc-placement="bottom-end" data-pc-hover
 *           aria-expanded="false">04</button>
 *   <div id="flag-3-q2" class="pc-popover" role="dialog" aria-hidden="true">
 *     ...<button data-pc-dismiss="popover">False positive</button>
 *   </div>
 * data-pc-hover: also opens on pointer hover (with a short grace period so the pointer can
 *   travel into the panel). Click / Enter always toggles, so it stays keyboard reachable.
 * data-pc-placement: see pc.place() — defaults to bottom-start.
 * The panel is position:fixed and placed with pc.place(), so it escapes scrolling tables.
 * Only one popover is open at a time; outside click / Escape / [data-pc-dismiss="popover"] close it.
 * JS API: pcPopover.open(id, triggerEl?) / close() / isOpen(id)
 * Events: `pc:open` / `pc:close` on the panel.
 */
(function () {
  let current = null; // { id, el, trigger, cleanup }
  let hoverTimer = null;

  function getEl(id) {
    return document.getElementById(id);
  }

  function triggerFor(id) {
    return document.querySelector(`[data-pc-trigger="popover"][data-pc-target="#${id}"]`);
  }

  function isOpen(id) {
    return !!current && current.id === id;
  }

  function reposition() {
    if (!current) return;
    pc.place(current.el, current.trigger, current.trigger.getAttribute("data-pc-placement") || "bottom-start", 6);
  }

  function close() {
    clearTimeout(hoverTimer);
    if (!current) return;
    const { el, trigger, cleanup } = current;
    el.setAttribute("data-pc-open", "false");
    el.setAttribute("aria-hidden", "true");
    trigger.setAttribute("aria-expanded", "false");
    cleanup();
    current = null;
    el.dispatchEvent(new CustomEvent("pc:close"));
  }

  function open(id, trigger) {
    clearTimeout(hoverTimer);
    if (isOpen(id)) return;
    close();
    const el = getEl(id);
    trigger = trigger || triggerFor(id);
    if (!el || !trigger) return;
    el.setAttribute("data-pc-open", "true");
    el.setAttribute("aria-hidden", "false");
    trigger.setAttribute("aria-expanded", "true");
    const unOutside = pc.onOutsideClick(el, (e) => {
      if (!trigger.contains(e.target)) close();
    });
    const unEsc = pc.onEscape(() => {
      close();
      trigger.focus();
    });
    const onEnter = () => clearTimeout(hoverTimer);
    const onLeave = () => scheduleClose();
    el.addEventListener("mouseenter", onEnter);
    el.addEventListener("mouseleave", onLeave);
    window.addEventListener("resize", reposition);
    document.addEventListener("scroll", reposition, true);
    current = {
      id, el, trigger,
      cleanup: () => {
        unOutside(); unEsc();
        el.removeEventListener("mouseenter", onEnter);
        el.removeEventListener("mouseleave", onLeave);
        window.removeEventListener("resize", reposition);
        document.removeEventListener("scroll", reposition, true);
      },
    };
    reposition();
    el.dispatchEvent(new CustomEvent("pc:open"));
  }

  function scheduleClose() {
    clearTimeout(hoverTimer);
    hoverTimer = setTimeout(close, 140);
  }

  window.pcPopover = { open, close, isOpen };

  document.addEventListener("click", (e) => {
    const trigger = e.target.closest('[data-pc-trigger="popover"]');
    if (trigger) {
      const id = pc.idFromTarget(trigger.getAttribute("data-pc-target"));
      if (!id) return;
      e.preventDefault();
      e.stopPropagation();
      // A hover-opened popover stays open on click (the click "pins" it rather than closing it).
      if (isOpen(id) && !trigger.hasAttribute("data-pc-hover")) close();
      else open(id, trigger);
      return;
    }
    if (e.target.closest('[data-pc-dismiss="popover"]')) close();
  });

  document.addEventListener("mouseover", (e) => {
    const trigger = e.target.closest('[data-pc-trigger="popover"][data-pc-hover]');
    if (!trigger) return;
    open(pc.idFromTarget(trigger.getAttribute("data-pc-target")), trigger);
  });

  document.addEventListener("mouseout", (e) => {
    const trigger = e.target.closest('[data-pc-trigger="popover"][data-pc-hover]');
    if (!trigger || trigger.contains(e.relatedTarget)) return;
    if (current && current.trigger === trigger && !current.el.contains(e.relatedTarget)) scheduleClose();
  });
})();
