/**
 * pc-tooltip.js — dark hover/focus tooltips (collapsed-sidebar labels, icon buttons, truncated values).
 * Markup contract:
 *   <button data-pc-tooltip="Copy link" data-pc-placement="top">...</button>
 * data-pc-placement: top (default) | bottom | left | right, optionally -start / -end.
 * data-pc-tooltip-only="collapsed": only show while the desktop sidebar rail is collapsed
 *   (used on nav items, whose labels are visible otherwise).
 * One tooltip element is shared by the whole page and positioned with pc.place(), so it is
 * never clipped by overflow:hidden/auto ancestors.
 * JS API: pcTooltip.show(el) / hide()
 */
(function () {
  let tip = null;
  let arrow = null;
  let text = null;
  let current = null;

  function ensure() {
    if (tip) return;
    tip = document.createElement("div");
    tip.className = "pc-tooltip";
    tip.setAttribute("role", "tooltip");
    tip.id = "pc-tooltip";
    tip.hidden = true;
    text = document.createElement("span");
    arrow = document.createElement("span");
    arrow.className = "pc-tooltip-arrow";
    tip.append(arrow, text);
    document.body.appendChild(tip);
  }

  function allowed(el) {
    if (el.getAttribute("data-pc-tooltip-only") !== "collapsed") return true;
    return document.documentElement.getAttribute("data-pc-sidebar") === "collapsed" && window.innerWidth >= 1024;
  }

  function show(el) {
    const label = el.getAttribute("data-pc-tooltip");
    if (!label || !allowed(el)) return hide();
    ensure();
    current = el;
    text.textContent = label;
    tip.hidden = false;
    el.setAttribute("aria-describedby", tip.id);
    const side = pc.place(tip, el, el.getAttribute("data-pc-placement") || "top", 10);
    const s = arrow.style;
    s.top = s.left = s.right = s.bottom = s.transform = "";
    if (side === "right") { s.left = "-4px"; s.top = "50%"; s.transform = "translateY(-50%) rotate(45deg)"; }
    else if (side === "left") { s.right = "-4px"; s.top = "50%"; s.transform = "translateY(-50%) rotate(45deg)"; }
    else if (side === "bottom") { s.top = "-4px"; s.left = "50%"; s.transform = "translateX(-50%) rotate(45deg)"; }
    else { s.bottom = "-4px"; s.left = "50%"; s.transform = "translateX(-50%) rotate(45deg)"; }
  }

  function hide() {
    if (current) current.removeAttribute("aria-describedby");
    current = null;
    if (tip) tip.hidden = true;
  }

  window.pcTooltip = { show, hide };

  document.addEventListener("mouseover", (e) => {
    const el = e.target.closest("[data-pc-tooltip]");
    if (el && el !== current) show(el);
    else if (!el && current) hide();
  });
  document.addEventListener("focusin", (e) => {
    const el = e.target.closest("[data-pc-tooltip]");
    if (el && e.target.matches(":focus-visible")) show(el);
  });
  document.addEventListener("focusout", hide);
  document.addEventListener("pointerdown", hide, true);
  document.addEventListener("scroll", hide, true);
  pc.onEscape(hide);
})();
