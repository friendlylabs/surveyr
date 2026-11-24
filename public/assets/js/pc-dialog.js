/**
 * pc-dialog.js — lightweight SweetAlert-style alert/confirm dialogs, built
 * from the same .pc-modal markup/CSS pc-modal.js uses, created and torn
 * down imperatively (no static markup needed in the page).
 *
 * JS API:
 *   pcDialog.fire({ title, text, icon, confirmButtonText, cancelButtonText, showCancelButton, confirmButtonVariant })
 *     -> Promise<{ isConfirmed: boolean, isDismissed: boolean }>
 *   pcDialog.alert({ title, text, icon, confirmButtonText })    // no cancel button
 *   pcDialog.confirm({ title, text, icon, confirmButtonText, cancelButtonText }) // with cancel button
 *
 * icon: 'success' | 'warning' | 'danger' | 'error' (alias of danger) | 'info' | 'question'
 * confirmButtonVariant: any pc-btn-* suffix ('primary', 'danger-solid', ...). Defaults to
 * 'danger-solid' for danger/error icons and 'primary' otherwise.
 *
 * A window.Swal.fire(...) alias is also exposed, so snippets written
 * against SweetAlert2's fire() API work unmodified without loading the
 * real library.
 */
(function () {
  const ICON_GLYPHS = {
    success: "✓",
    warning: "!",
    danger: "✕",
    error: "✕",
    info: "i",
    question: "?",
  };

  function iconClass(icon) {
    return `pc-dialog-icon-${icon === "error" ? "danger" : icon}`;
  }

  let seq = 0;

  function fire(options) {
    const {
      title = "",
      text = "",
      icon = "info",
      confirmButtonText = "OK",
      cancelButtonText = "Cancel",
      showCancelButton = false,
      confirmButtonVariant = null,
    } = options || {};

    return new Promise((resolve) => {
      const variant = confirmButtonVariant || (icon === "error" || icon === "danger" ? "danger-solid" : "primary");
      const titleId = `pc-dialog-title-${++seq}`;

      const root = document.createElement("div");
      root.className = "pc-modal pc-modal-sm";
      root.setAttribute("role", "alertdialog");
      root.setAttribute("aria-modal", "true");
      if (title) root.setAttribute("aria-labelledby", titleId);
      root.innerHTML = `
        <div class="pc-modal-backdrop"></div>
        <div class="pc-modal-panel">
          <div class="flex flex-col items-center gap-3 p-6 text-center">
            <div class="pc-dialog-icon ${iconClass(icon)}" aria-hidden="true">${ICON_GLYPHS[icon] || ICON_GLYPHS.info}</div>
            <div>
              ${title ? `<div id="${titleId}" class="text-base font-semibold">${pc.escapeHtml(title)}</div>` : ""}
              ${text ? `<div class="mt-1 text-[13px] text-pc-muted">${pc.escapeHtml(text)}</div>` : ""}
            </div>
            <div class="mt-2 flex w-full gap-2">
              ${showCancelButton ? `<button type="button" data-pc-dialog-cancel class="pc-btn pc-btn-secondary flex-1">${pc.escapeHtml(cancelButtonText)}</button>` : ""}
              <button type="button" data-pc-dialog-confirm data-pc-autofocus class="pc-btn pc-btn-${variant} flex-1">${pc.escapeHtml(confirmButtonText)}</button>
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

  function alert(options) {
    return fire({ ...options, showCancelButton: false });
  }

  function confirm(options) {
    return fire({ ...options, showCancelButton: true });
  }

  window.pcDialog = { fire, alert, confirm };
  window.Swal = window.Swal || { fire };
})();
