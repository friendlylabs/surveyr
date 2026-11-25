/**
 * pc-core.js — shared low-level helpers used by every pc- interactive component.
 * Exposes a single `pc` global namespace (no build step / no modules required).
 * Load this first; every other pc-*.js file depends on it.
 */
(function () {
  const pc = window.pc || (window.pc = {});

  pc.qs = (sel, ctx) => (ctx || document).querySelector(sel);
  pc.qsa = (sel, ctx) => Array.from((ctx || document).querySelectorAll(sel));

  /**
   * The text in the page's language, the JS side of Laravel's __(): looks it up in window.pcStrings (the layout
   * hands over lang/{locale}.json) and falls back to the text itself; `:name` placeholders are filled from `replace`.
   *   pc.t("Choose a date")
   *   pc.t(":count selected", { count: 3 })
   */
  pc.t = function (text, replace) {
    const strings = window.pcStrings || {};
    let out = Object.prototype.hasOwnProperty.call(strings, text) ? strings[text] : text;
    if (replace) {
      Object.keys(replace).forEach((key) => {
        out = out.split(":" + key).join(String(replace[key]));
      });
    }
    return out;
  };

  /** True when the page reads right to left (<html dir="rtl">). */
  pc.rtl = () => document.documentElement.dir === "rtl";

  pc.escapeHtml = function (str) {
    return String(str ?? "").replace(/[&<>"']/g, (c) => ({
      "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;",
    }[c]));
  };

  pc.initials = function (name) {
    return String(name || "")
      .split(/\s+/)
      .filter(Boolean)
      .map((w) => w[0])
      .join("")
      .slice(0, 2)
      .toUpperCase();
  };

  /** Current wall-clock time as HH:MM — used for audit-trail stamps. */
  pc.now = function () {
    const d = new Date();
    return String(d.getHours()).padStart(2, "0") + ":" + String(d.getMinutes()).padStart(2, "0");
  };

  const FOCUSABLE = 'a[href], button:not([disabled]), textarea:not([disabled]), input:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])';

  pc.trapFocus = function (container) {
    if (!container) return () => {};
    function isVisible(el) {
      return !!(el.offsetWidth || el.offsetHeight || el.getClientRects().length);
    }
    function handleKeydown(e) {
      if (e.key !== "Tab") return;
      const focusable = pc.qsa(FOCUSABLE, container).filter(isVisible);
      if (!focusable.length) return;
      const first = focusable[0];
      const last = focusable[focusable.length - 1];
      if (e.shiftKey && document.activeElement === first) {
        e.preventDefault();
        last.focus();
      } else if (!e.shiftKey && document.activeElement === last) {
        e.preventDefault();
        first.focus();
      }
    }
    container.addEventListener("keydown", handleKeydown);
    return () => container.removeEventListener("keydown", handleKeydown);
  };

  pc.focusFirst = function (container) {
    if (!container) return;
    const target =
      container.querySelector("[data-pc-autofocus]") ||
      pc.qsa(FOCUSABLE, container)[0] ||
      container;
    requestAnimationFrame(() => target && target.focus());
  };

  let scrollLockCount = 0;
  pc.lockScroll = function (lock) {
    scrollLockCount = Math.max(0, scrollLockCount + (lock ? 1 : -1));
    document.documentElement.style.overflow = scrollLockCount > 0 ? "hidden" : "";
  };

  pc.onOutsideClick = function (el, cb) {
    function handler(e) {
      // The overlay scrollbar (pc-scrollbar.js) floats outside every panel; using it isn't leaving the panel.
      if (e.target.closest && e.target.closest(".pc-scrollbar")) return;
      if (el && !el.contains(e.target)) cb(e);
    }
    document.addEventListener("pointerdown", handler, true);
    return () => document.removeEventListener("pointerdown", handler, true);
  };

  pc.onEscape = function (cb) {
    function handler(e) {
      if (e.key === "Escape") cb(e);
    }
    document.addEventListener("keydown", handler);
    return () => document.removeEventListener("keydown", handler);
  };

  pc.idFromTarget = function (targetSel) {
    return (targetSel || "").replace(/^#/, "");
  };

  /** Debounces `fn`: calls within `wait`ms of each other collapse into one trailing call. */
  pc.debounce = function (fn, wait) {
    let timer;
    return function (...args) {
      clearTimeout(timer);
      timer = setTimeout(() => fn.apply(this, args), wait);
    };
  };

  /**
   * Places a `position: fixed` element next to `anchor`. placement: 'top' | 'bottom' | 'left' | 'right', optionally
   * '-start' / '-end' (e.g. 'bottom-end'). Flips when the side would overflow, then clamps to the viewport.
   * Returns the side used, so callers can point an arrow at the anchor.
   * Placements are written for left-to-right pages; on a right-to-left page they mirror ('right' opens on the left,
   * '-start' / '-end' align to the anchor's right / left edge), and the side returned is the physical one.
   */
  pc.place = function (floating, anchor, placement, gap) {
    let [side0, align = "center"] = String(placement || "bottom").split("-");
    if (pc.rtl()) {
      const mirror = { left: "right", right: "left", start: "end", end: "start" };
      side0 = mirror[side0] || side0;
      align = mirror[align] || align;
    }
    const g = gap == null ? 8 : gap;
    const a = anchor.getBoundingClientRect();
    const f = floating.getBoundingClientRect();
    const vw = window.innerWidth;
    const vh = window.innerHeight;
    const pad = 8;
    const opposite = { top: "bottom", bottom: "top", left: "right", right: "left" };
    const fits = {
      top: a.top - f.height - g >= pad,
      bottom: a.bottom + f.height + g <= vh - pad,
      left: a.left - f.width - g >= pad,
      right: a.right + f.width + g <= vw - pad,
    };
    const side = !fits[side0] && fits[opposite[side0]] ? opposite[side0] : side0;

    let top;
    let left;
    if (side === "top" || side === "bottom") {
      top = side === "top" ? a.top - f.height - g : a.bottom + g;
      left = align === "start" ? a.left : align === "end" ? a.right - f.width : a.left + a.width / 2 - f.width / 2;
    } else {
      left = side === "left" ? a.left - f.width - g : a.right + g;
      top = align === "start" ? a.top : align === "end" ? a.bottom - f.height : a.top + a.height / 2 - f.height / 2;
    }
    left = Math.min(Math.max(pad, left), vw - f.width - pad);
    top = Math.min(Math.max(pad, top), vh - f.height - pad);
    floating.style.top = Math.round(top) + "px";
    floating.style.left = Math.round(left) + "px";
    return side;
  };

  /**
   * fetch() with the app's JSON headers and CSRF token; a string body is sent as JSON.
   * For calls that aren't a form post (submitForm in app.js covers those).
   */
  pc.jsonFetch = function (url, options) {
    options = options || {};
    const token = document.querySelector('meta[name="csrf-token"]');
    return fetch(url, {
      ...options,
      headers: {
        Accept: "application/json",
        "X-Requested-With": "XMLHttpRequest",
        "X-CSRF-TOKEN": token ? token.content : "",
        ...(options.body ? { "Content-Type": "application/json" } : {}),
        ...(options.headers || {}),
      },
    });
  };

  /**
   * Renders pc-dropdown-item options into a menu container, highlighting the current one and wiring each item's
   * click to `onPick`.
   */
  pc.renderOptionMenu = function (containerId, options, current, onPick) {
    const el = document.getElementById(containerId);
    if (!el) return;
    el.innerHTML = options
      .map((label) => `<button type="button" class="pc-dropdown-item ${label === current ? "pc-dropdown-item-active" : ""}" data-pc-value="${pc.escapeHtml(label)}">${pc.escapeHtml(label)}</button>`)
      .join("");
    pc.qsa("[data-pc-value]", el).forEach((item) => {
      item.addEventListener("click", () => {
        onPick(item.getAttribute("data-pc-value"));
        if (window.pcDropdown) pcDropdown.closeAll();
      });
    });
  };
})();
