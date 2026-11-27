/**
 * pc-dialog.js — alert / confirm dialogs.
 *
 * A compact card: the title with a small type icon on its right, the message under it, and the actions bottom-right
 * (a soft Cancel, then the confirm button in the dialog type's colour). Built from the .pc-modal markup pc-modal.js
 * uses, created and torn down imperatively, so pages need no static markup.
 *
 * JS API:
 *   pcDialog.fire({ title, text, icon, confirmButtonText, cancelButtonText, showCancelButton, confirmButtonVariant })
 *     -> Promise<{ isConfirmed: boolean, isDismissed: boolean }>
 *   pcDialog.alert({ title, text, icon, confirmButtonText })                        (no cancel button)
 *   pcDialog.confirm({ title, text, icon, confirmButtonText, cancelButtonText })    (with a cancel button)
 *
 * icon (the dialog type): 'primary' | 'question' | 'info' | 'success' | 'warning' | 'danger' | 'error' (alias of
 * danger). It picks the icon and the confirm button's colour: brand for primary / question, then info, success,
 * warning and danger in their status colours. confirmButtonVariant overrides the button with any pc-btn-* suffix.
 *
 * A window.Swal.fire(...) alias is also exposed, so snippets written against SweetAlert2's fire() API work
 * unmodified without loading the real library.
 */
(function () {
  // per type: the Phosphor icon by the title, and the confirm button's pc-btn-* variant
  const TYPES = {
    primary: { icon: "fa-sparkles", button: "brand" },
    question: { icon: "fa-circle-question", button: "brand" },
    info: { icon: "fa-circle-info", button: "info-solid" },
    success: { icon: "fa-circle-check", button: "success-solid" },
    warning: { icon: "fa-triangle-exclamation", button: "warning-solid" },
    danger: { icon: "fa-octagon-exclamation", button: "danger-solid" },
  };

  let seq = 0;

  /** The type's key, with 'error' read as danger and anything unknown as primary. */
  function typeOf(icon) {
    if (icon === "error") return "danger";
    return TYPES[icon] ? icon : "primary";
  }

  /**
   * Opens the dialog and resolves once it closes: confirmed by its button, dismissed by Cancel, the backdrop or
   * Escape. Focus stays inside while open and goes back afterwards.
   */
  function fire(options) {
    const {
      title = "",
      text = "",
      icon = "primary",
      confirmButtonText = pc.t("OK"),
      cancelButtonText = pc.t("Cancel"),
      showCancelButton = false,
      confirmButtonVariant = null,
    } = options || {};

    return new Promise((resolve) => {
      const type = typeOf(icon);
      const variant = confirmButtonVariant || TYPES[type].button;
      const titleId = `pc-dialog-title-${++seq}`;
      const textId = `pc-dialog-text-${seq}`;

      const root = document.createElement("div");
      root.className = "pc-modal pc-modal-sm pc-dialog";
      root.setAttribute("role", "alertdialog");
      root.setAttribute("aria-modal", "true");
      if (title) root.setAttribute("aria-labelledby", titleId);
      if (text) root.setAttribute("aria-describedby", textId);
      root.innerHTML = `
        <div class="pc-modal-backdrop"></div>
        <div class="pc-modal-panel">
          <div class="pc-dialog-body">
            <div class="pc-dialog-head">
              ${title ? `<div id="${titleId}" class="pc-dialog-title">${pc.escapeHtml(title)}</div>` : ""}
              <i class="fa-duotone ${TYPES[type].icon} pc-dialog-icon pc-dialog-icon-${type}" aria-hidden="true"></i>
            </div>
            ${text ? `<div id="${textId}" class="pc-dialog-text">${pc.escapeHtml(text)}</div>` : ""}
            <div class="pc-dialog-actions">
              ${showCancelButton ? `<button type="button" data-pc-dialog-cancel class="pc-btn pc-dialog-cancel">${pc.escapeHtml(cancelButtonText)}</button>` : ""}
              <button type="button" data-pc-dialog-confirm data-pc-autofocus class="pc-btn pc-btn-${variant}">${pc.escapeHtml(confirmButtonText)}</button>
            </div>
          </div>
        </div>
      `;

      document.body.appendChild(root);
      const lastFocused = document.activeElement;
      root.setAttribute("data-pc-open", "true");
      pc.lockScroll(true);
      const panel = root.querySelector(".pc-modal-panel");
      pc.focusFirst(panel);
      const untrap = pc.trapFocus(panel);

      /** Closes the dialog, gives focus back and resolves with the result. */
      function settle(result) {
        untrap();
        unesc();
        pc.lockScroll(false);
        root.remove();
        if (lastFocused && typeof lastFocused.focus === "function") lastFocused.focus();
        resolve(result);
      }

      const unesc = pc.onEscape(() => settle({ isConfirmed: false, isDismissed: true }));

      root.addEventListener("click", (e) => {
        if (e.target.closest("[data-pc-dialog-confirm]")) {
          settle({ isConfirmed: true, isDismissed: false });
        } else if (e.target.closest("[data-pc-dialog-cancel]") || e.target.closest(".pc-modal-backdrop")) {
          settle({ isConfirmed: false, isDismissed: true });
        }
      });
    });
  }

  /** A dialog with only the confirm button. */
  function alert(options) {
    return fire({ ...options, showCancelButton: false });
  }

  /** A dialog with Cancel and the confirm button. */
  function confirm(options) {
    return fire({ ...options, showCancelButton: true });
  }

  window.pcDialog = { fire, alert, confirm };
  window.Swal = window.Swal || { fire };
})();
