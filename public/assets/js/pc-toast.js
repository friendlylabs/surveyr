/**
 * pc-toast.js — bottom-center stacking toast notifications.
 * The toast itself always keeps the same dark pc-ink styling — `type` only
 * colors a small leading dot, it never recolors the whole toast.
 * JS API:
 *   pcToast.show('Message', { type: 'neutral'|'success'|'warning'|'danger'|'info', duration: 2200,
 *                            action: { label: 'Undo', onClick: () => {} } })
 *   pcToast.success('Message', opts) / warning(...) / danger(...) / info(...)
 * Returns the toast element; pcToast.dismiss(el) removes it early.
 */
(function () {
  let viewport = null;

  function ensureViewport() {
    if (viewport && document.body.contains(viewport)) return viewport;
    viewport = document.querySelector(".pc-toast-viewport");
    if (!viewport) {
      viewport = document.createElement("div");
      viewport.className = "pc-toast-viewport";
      viewport.setAttribute("aria-live", "polite");
      viewport.setAttribute("aria-atomic", "false");
      document.body.appendChild(viewport);
    }
    return viewport;
  }

  function dismiss(el) {
    if (!el || el.getAttribute("data-pc-leaving") === "true") return;
    el.setAttribute("data-pc-leaving", "true");
    setTimeout(() => el.remove(), 200);
  }

  function show(message, opts) {
    const { type = "neutral", duration = 2200, action = null } = opts || {};
    const vp = ensureViewport();

    const el = document.createElement("div");
    el.className = "pc-toast";
    el.setAttribute("role", type === "danger" ? "alert" : "status");

    const dot = document.createElement("span");
    dot.className = `pc-toast-dot pc-toast-dot-${type}`;
    el.appendChild(dot);

    const text = document.createElement("span");
    text.textContent = message;
    el.appendChild(text);

    if (action && action.label) {
      const btn = document.createElement("button");
      btn.type = "button";
      btn.className = "pc-toast-action";
      btn.textContent = action.label;
      btn.addEventListener("click", () => {
        action.onClick && action.onClick();
        dismiss(el);
      });
      el.appendChild(btn);
    }

    vp.appendChild(el);
    let timer = setTimeout(() => dismiss(el), duration);
    // Hovering keeps the toast up so an action button can be reached.
    el.addEventListener("mouseenter", () => clearTimeout(timer));
    el.addEventListener("mouseleave", () => { timer = setTimeout(() => dismiss(el), 1200); });
    return el;
  }

  const shorthand = (type) => (message, opts) => show(message, { ...(opts || {}), type });

  window.pcToast = {
    show,
    dismiss,
    success: shorthand("success"),
    warning: shorthand("warning"),
    danger: shorthand("danger"),
    info: shorthand("info"),
  };
})();
