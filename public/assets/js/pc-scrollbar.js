/**
 * pc-scrollbar.js — overlay scrollbars for inner scroll areas (tables, cards, lists, flyouts).
 * Native scrollbars reserve a gutter even when hidden, so on mouse devices they're turned off
 * (html.pc-overlay-scroll, see app.css) and one floating thumb per axis is drawn over the area under
 * the pointer instead: it takes no space, fades in on hover, and can be dragged or its track clicked.
 * The page's own scrollbar stays native. Touch devices keep the (already overlay) native bars.
 * Put this in <head> to drop the gutters before first paint:
 *   <script>matchMedia("(hover: hover) and (pointer: fine)").matches&&document.documentElement.classList.add("pc-overlay-scroll")</script>
 */
(function () {
  if (!window.matchMedia("(hover: hover) and (pointer: fine)").matches) return;

  const root = document.documentElement;
  root.classList.add("pc-overlay-scroll");

  const GAP = 2; // inset from the area's edges
  const MIN = 24; // shortest thumb
  const AXES = {
    y: { size: "height", pos: "top", client: "clientHeight", scroll: "scrollHeight", offset: "scrollTop", coord: "clientY" },
    x: { size: "width", pos: "left", client: "clientWidth", scroll: "scrollWidth", offset: "scrollLeft", coord: "clientX" },
  };
  const bars = {};
  const targets = { y: null, x: null };
  let drag = null;
  let frame = 0;

  function overflows(el, axis) {
    const a = AXES[axis];
    const style = getComputedStyle(el)[axis === "y" ? "overflowY" : "overflowX"];
    return (style === "auto" || style === "scroll") && el[a.scroll] > el[a.client] + 1;
  }

  /** Whether the area scrolls right to left (its own direction, normally inherited from <html dir>). */
  function rtl(el) {
    return getComputedStyle(el).direction === "rtl";
  }

  /** Nearest scrollable ancestor per axis, so a sideways table inside a tall card gets both bars. */
  function resolve(node) {
    const found = { y: null, x: null };
    for (let el = node; el && el !== document.body && el !== root; el = el.parentElement) {
      if (!found.y && overflows(el, "y")) found.y = el;
      if (!found.x && overflows(el, "x")) found.x = el;
      if (found.y && found.x) break;
    }
    return found;
  }

  function build(axis) {
    const bar = document.createElement("div");
    bar.className = "pc-scrollbar";
    bar.dataset.axis = axis;
    bar.setAttribute("aria-hidden", "true");
    bar.innerHTML = '<div class="pc-scrollbar-thumb"></div>';
    bar.addEventListener("pointerdown", (e) => onDown(e, axis));
    document.body.appendChild(bar);
    return bar;
  }

  function place(axis) {
    const bar = bars[axis];
    const el = targets[axis];
    if (!el || !el.isConnected || !overflows(el, axis)) {
      bar.removeAttribute("data-show");
      targets[axis] = null;
      return;
    }
    const a = AXES[axis];
    const rect = el.getBoundingClientRect();
    const track = el[a.client] - GAP * 2;
    const thumb = Math.max(MIN, (track * el[a.client]) / el[a.scroll]);
    const range = el[a.scroll] - el[a.client];
    let at = range > 0 ? ((track - thumb) * el[a.offset]) / range : 0;
    // RTL: scrollLeft runs from 0 (scrolled to the right edge) down to -range, so the thumb starts at the track's right end
    if (axis === "x" && rtl(el)) at += track - thumb;

    if (axis === "y") {
      bar.style.top = rect.top + el.clientTop + GAP + "px";
      // the vertical bar sits on the inline-end side: right on LTR pages, left on RTL ones
      bar.style.left = (rtl(el) ? rect.left + el.clientLeft + GAP : rect.left + el.clientLeft + el.clientWidth - bar.offsetWidth - GAP) + "px";
    } else {
      bar.style.left = rect.left + el.clientLeft + GAP + "px";
      bar.style.top = rect.top + el.clientTop + el.clientHeight - bar.offsetHeight - GAP + "px";
    }
    bar.style[a.size] = track + "px";
    const t = bar.firstElementChild;
    t.style[a.size] = thumb + "px";
    t.style.transform = axis === "y" ? `translateY(${at}px)` : `translateX(${at}px)`;
    bar.setAttribute("data-show", "");
  }

  function update() {
    frame = 0;
    place("y");
    place("x");
  }
  function schedule() {
    if (!frame) frame = requestAnimationFrame(update);
  }

  function hide() {
    if (drag) return;
    targets.y = targets.x = null;
    schedule();
  }

  function onDown(e, axis) {
    const el = targets[axis];
    if (!el || e.button !== 0) return;
    e.preventDefault();
    const a = AXES[axis];
    const thumb = bars[axis].firstElementChild;
    if (e.target !== thumb) {
      // Track click: page toward the pointer.
      const before = e[a.coord] < thumb.getBoundingClientRect()[a.pos];
      el.scrollBy({ [a.pos]: (before ? -1 : 1) * el[a.client] * 0.9 });
      return;
    }
    const track = bars[axis][axis === "y" ? "clientHeight" : "clientWidth"];
    const range = el[a.scroll] - el[a.client];
    drag = { axis, el, start: e[a.coord], from: el[a.offset], ratio: range / Math.max(1, track - thumb[axis === "y" ? "offsetHeight" : "offsetWidth"]) };
    el.style.scrollBehavior = "auto";
    bars[axis].setAttribute("data-dragging", "");
    root.classList.add("pc-scrollbar-dragging");
    bars[axis].setPointerCapture(e.pointerId);
  }

  function onMove(e) {
    if (!drag) return;
    const a = AXES[drag.axis];
    drag.el[a.offset] = drag.from + (e[a.coord] - drag.start) * drag.ratio;
  }

  function onUp(e) {
    if (!drag) return;
    const bar = bars[drag.axis];
    drag.el.style.scrollBehavior = "";
    bar.removeAttribute("data-dragging");
    root.classList.remove("pc-scrollbar-dragging");
    drag = null;
    if (!bar.matches(":hover")) {
      const under = document.elementFromPoint(e.clientX, e.clientY);
      if (under) Object.assign(targets, resolve(under));
      schedule();
    }
  }

  function init() {
    bars.y = build("y");
    bars.x = build("x");

    document.addEventListener("pointerover", (e) => {
      if (drag || e.pointerType === "touch" || e.target.closest(".pc-scrollbar")) return;
      Object.assign(targets, resolve(e.target));
      schedule();
    });
    document.addEventListener("pointerout", (e) => !e.relatedTarget && hide());
    document.addEventListener("pointermove", (e) => (drag ? onMove(e) : (targets.y || targets.x) && schedule()));
    document.addEventListener("pointerup", onUp);
    document.addEventListener("pointercancel", onUp);
    document.addEventListener("scroll", schedule, true);
    window.addEventListener("resize", schedule);
  }

  if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", init);
  else init();
})();
