/**
 * pc-dropdown.js — menu dropdowns (avatar menu, row action menus, filter menus).
 * Markup contract:
 *   <div class="pc-dropdown">
 *     <button data-pc-trigger="dropdown" data-pc-target="#userMenu" aria-expanded="false">...</button>
 *     <div id="userMenu" class="pc-dropdown-menu pc-dropdown-menu-end" aria-hidden="true">
 *       <button class="pc-dropdown-item">Profile</button>
 *       <button class="pc-dropdown-item" data-pc-dismiss="dropdown">Close menu</button>
 *     </div>
 *   </div>
 * Only one pc-dropdown menu is open at a time; outside click / Escape closes it.
 * Up/Down arrow keys move focus between items while a menu is open.
 * Inside an overflow container (table wrapper, card) the open menu floats (data-pc-floating, position fixed)
 * so it isn't clipped; it opens upwards when there's no room below and follows the trigger on scroll.
 * JS API: pcDropdown.open('userMenu') / close('userMenu') / toggle('userMenu') / closeAll() / isOpen(id)
 * Events: `pc:open` on the menu element.
 */
(function () {
  let current = null; // { id, cleanup }

  function getEl(id) {
    return document.getElementById(id);
  }

  function getTrigger(id) {
    return document.querySelector(`[data-pc-trigger="dropdown"][data-pc-target="#${id}"]`);
  }

  function items(el) {
    return pc.qsa(".pc-dropdown-item:not([disabled])", el);
  }

  /**
   * A menu inside a scrolling / overflow-hidden box (a table wrapper, a card) would be clipped, so it floats instead:
   * position fixed, placed from the trigger's rect, flipped up when there's no room below.
   */
  function clipped(el) {
    for (let node = el.parentElement; node && node !== document.body; node = node.parentElement) {
      const style = getComputedStyle(node);
      if (style.overflowX !== "visible" || style.overflowY !== "visible") return true;
    }
    return false;
  }

  function place(el, trigger) {
    const GAP = 8;
    const EDGE = 8;
    const r = trigger.getBoundingClientRect();
    const w = el.offsetWidth;
    const h = el.offsetHeight;
    // start / end are the trigger's inline edges: left / right on an LTR page, right / left on an RTL one
    const alignRight = el.classList.contains("pc-dropdown-menu-end") !== pc.rtl();
    const left = Math.max(EDGE, Math.min(alignRight ? r.right - w : r.left, window.innerWidth - w - EDGE));
    const below = r.bottom + GAP;
    const top = below + h > window.innerHeight - EDGE && r.top - GAP - h >= EDGE ? r.top - GAP - h : below;
    el.style.left = left + "px";
    el.style.top = top + "px";
  }

  function float(el, trigger) {
    el.setAttribute("data-pc-floating", "true");
    place(el, trigger);
    const reposition = () => place(el, trigger);
    window.addEventListener("scroll", reposition, true);
    window.addEventListener("resize", reposition);
    return () => {
      window.removeEventListener("scroll", reposition, true);
      window.removeEventListener("resize", reposition);
      el.removeAttribute("data-pc-floating");
      el.style.left = el.style.top = "";
    };
  }

  function closeAll() {
    if (!current) return;
    const { id, cleanup } = current;
    const el = getEl(id);
    if (el) {
      el.setAttribute("data-pc-open", "false");
      el.setAttribute("aria-hidden", "true");
    }
    const trigger = getTrigger(id);
    if (trigger) trigger.setAttribute("aria-expanded", "false");
    cleanup && cleanup();
    current = null;
  }

  function isOpen(id) {
    return !!current && current.id === id;
  }

  function open(id) {
    if (isOpen(id)) return;
    closeAll();
    const el = getEl(id);
    if (!el) return;
    const trigger = getTrigger(id);
    el.setAttribute("data-pc-open", "true");
    el.setAttribute("aria-hidden", "false");
    if (trigger) trigger.setAttribute("aria-expanded", "true");
    const unFloat = trigger && clipped(el) ? float(el, trigger) : null;
    const unOutside = pc.onOutsideClick(el, (e) => {
      if (trigger && trigger.contains(e.target)) return;
      closeAll();
    });
    const unEsc = pc.onEscape(() => {
      closeAll();
      if (trigger) trigger.focus();
    });
    function onKey(e) {
      if (e.key !== "ArrowDown" && e.key !== "ArrowUp") return;
      const list = items(el);
      if (!list.length) return;
      e.preventDefault();
      const i = list.indexOf(document.activeElement);
      const next = e.key === "ArrowDown" ? (i + 1) % list.length : (i - 1 + list.length) % list.length;
      list[next].focus();
    }
    document.addEventListener("keydown", onKey);
    current = { id, cleanup: () => { unOutside(); unEsc(); unFloat && unFloat(); document.removeEventListener("keydown", onKey); } };
    el.dispatchEvent(new CustomEvent("pc:open"));
  }

  function close(id) {
    if (isOpen(id)) closeAll();
  }

  function toggle(id) {
    isOpen(id) ? closeAll() : open(id);
  }

  window.pcDropdown = { open, close, toggle, closeAll, isOpen };

  document.addEventListener("click", (e) => {
    const trigger = e.target.closest('[data-pc-trigger="dropdown"]');
    if (trigger) {
      const id = pc.idFromTarget(trigger.getAttribute("data-pc-target"));
      if (id) {
        e.preventDefault();
        e.stopPropagation();
        toggle(id);
      }
      return;
    }
    if (e.target.closest('[data-pc-dismiss="dropdown"]')) closeAll();
  });
})();
