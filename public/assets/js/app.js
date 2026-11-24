/**
 * app.js — app-wide behaviour shared by every page. Loaded after the pc-*.js components.
 *   - window.toast: `{ message }`-shaped wrapper around pcToast
 *   - buttonState(button, 'loading' | 'reset')
 *   - submitForm(event, responseHandler?): AJAX-submit a form against the Controller::jsonSuccess / jsonError contract
 *   - copyToClipboard(textOrElement), underDevelopment(event?)
 *   - [data-pc-confirm]: confirm with pcDialog before following a link / running an action
 *   - clearFormErrors(form), fillForm(form, fields)
 *   - [data-pc-edit="#modal"] + data-pc-payload: fill a shared edit modal from the clicked row and open it
 *   - .pc-modal[data-pc-reset]: its form is emptied each time the modal opens (add dialogs)
 *   - <meta name="pc-toast">: a message flashed by the server (session toast) is toasted on load
 */

window.toast = {
  success: ({ message, duration } = {}) => window.pcToast.success(message, { duration }),
  error: ({ message, duration } = {}) => window.pcToast.danger(message, { duration }),
  warning: ({ message, duration } = {}) => window.pcToast.warning(message, { duration }),
  info: ({ message, duration } = {}) => window.pcToast.info(message, { duration }),
};

/**
 * Toggle an action button between its normal and a disabled loading state.
 * `button` may be an element or a selector string.
 */
function buttonState(button, state, initialText = null) {
  const el = typeof button === "string" ? document.querySelector(button) : button;
  if (!el) return;

  if (state === "loading") {
    el.dataset.initialHtml = el.innerHTML;
    el.style.minWidth = el.offsetWidth + "px";
    el.disabled = true;
    el.setAttribute("aria-busy", "true");
    el.innerHTML = '<span class="pc-spinner" aria-hidden="true"></span>';
  } else {
    el.disabled = false;
    el.removeAttribute("aria-busy");
    el.style.minWidth = "";
    el.innerHTML = initialText ?? el.dataset.initialHtml ?? el.innerHTML;
  }
}

/**
 * Show / clear a field's inline error. The error element is the .pc-field-error
 * among the input's aria-describedby ids (e.g. email -> #emailError).
 */
function fieldError(form, name, message) {
  // array fields post as name[]; Laravel reports them as `name` or `name.0`
  const base = name.replace(/(\[\]|\.\d+)$/, "");
  const input = form.querySelector(`[name="${CSS.escape(name)}"]`) || form.querySelector(`[name="${CSS.escape(base)}[]"]`);
  if (!input) return false;
  const ids = (input.getAttribute("aria-describedby") || "").split(/\s+/).filter(Boolean);
  const box = ids.map((id) => document.getElementById(id)).find((el) => el && el.classList.contains("pc-field-error"));
  if (message) input.setAttribute("aria-invalid", "true");
  else input.removeAttribute("aria-invalid");
  if (!box) return false;
  box.textContent = message || "";
  box.hidden = !message;
  return true;
}

/**
 * Submit a form over fetch and handle the JSON the controllers return
 * ({ success, message, redirect?, ... } from Controller::jsonSuccess / jsonError).
 *   <form method="POST" action="..." onsubmit="submitForm(event)">
 * Laravel 422 validation errors land in each field's .pc-field-error; anything
 * without one is toasted. Pass `responseHandler(data, response)` to take over success handling.
 */
async function submitForm(event, responseHandler = null) {
  event.preventDefault();
  const form = event.target.closest("form");
  const submitButton = event.submitter || form.querySelector('button[type="submit"]');

  form.querySelectorAll('[aria-invalid="true"][name]').forEach((el) => fieldError(form, el.name, null));
  if (submitButton) buttonState(submitButton, "loading");

  try {
    const response = await fetch(form.action, {
      method: (form.getAttribute("method") || "POST").toUpperCase(),
      body: new FormData(form),
      headers: {
        Accept: "application/json",
        "X-Requested-With": "XMLHttpRequest",
        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.content || "",
      },
    });
    const data = await response.json().catch(() => ({}));

    if (response.status === 422 && data.errors) {
      const unplaced = Object.entries(data.errors).filter(([name, messages]) => !fieldError(form, name, messages[0]));
      const first = form.querySelector('[aria-invalid="true"]');
      if (first) first.focus();
      if (unplaced.length) toast.error({ message: unplaced[0][1][0] });
      return;
    }
    if (response.status === 419) {
      toast.error({ message: "Your session expired. Reload the page and try again." });
      return;
    }

    if (responseHandler && typeof responseHandler === "function") {
      responseHandler(data, response);
      return;
    }

    if (response.ok && data.success) {
      if (data.message) toast.success({ message: data.message });
      if (data.redirect) setTimeout(() => (window.location.href = data.redirect), 800);
    } else {
      toast.error({ message: data.message || "An unknown error occurred." });
    }
  } catch (err) {
    toast.error({ message: "Couldn't reach the server. Check your connection and try again." });
  } finally {
    if (submitButton && document.body.contains(submitButton)) buttonState(submitButton, "reset");
  }
}

/** Copy a string, or an element's text / value, to the clipboard. */
async function copyToClipboard(el) {
  const value = el instanceof HTMLElement ? (el.value ?? el.innerText) : String(el ?? "");
  try {
    await navigator.clipboard.writeText(value);
  } catch (err) {
    // clipboard API needs a secure context; fall back for plain-http dev hosts
    const input = document.createElement("textarea");
    input.value = value;
    document.body.appendChild(input);
    input.select();
    document.execCommand("copy");
    input.remove();
  }
  toast.info({ message: "Copied to clipboard" });
}

/** Placeholder for links / buttons whose feature isn't built yet: onclick="underDevelopment(event)". */
function underDevelopment(event) {
  if (event) event.preventDefault();
  pcDialog.alert({
    title: "Under development",
    text: "This feature is under development and will be available soon.",
    icon: "info",
    confirmButtonText: "OK",
  });
}

/** Hide every inline field error in a form. */
function clearFormErrors(form) {
  form.querySelectorAll('[aria-invalid="true"][name]').forEach((el) => fieldError(form, el.name, null));
}

/**
 * Set form fields from { name: value }. Checkbox groups and multiple selects (name[]) take an array of
 * chosen values; null / undefined empty the field.
 */
function fillForm(form, fields) {
  Object.entries(fields || {}).forEach(([name, value]) => {
    const chosen = (Array.isArray(value) ? value : []).map(String);
    const boxes = form.querySelectorAll(`input[type="checkbox"][name="${CSS.escape(name)}[]"]`);
    if (boxes.length) {
      boxes.forEach((box) => (box.checked = chosen.includes(box.value)));
      return;
    }
    const multi = form.querySelector(`select[multiple][name="${CSS.escape(name)}[]"]`);
    if (multi) {
      Array.from(multi.options).forEach((option) => (option.selected = chosen.includes(option.value)));
      multi.dispatchEvent(new Event("change", { bubbles: true }));
      return;
    }
    const field = form.elements[name];
    if (field) field.value = value ?? "";
  });
}

(function () {
  /* Shared edit dialogs: one modal per page, filled from the clicked row.
   *   <button data-pc-edit="#editModule" data-pc-payload='{"action": "...", "title": "...", "subtitle": "...", "fields": {...}}'>
   * The modal's form posts to `action`; [data-title] / [data-subtitle] in the modal take `title` / `subtitle`. */
  document.addEventListener("click", (e) => {
    const trigger = e.target.closest("[data-pc-edit]");
    if (!trigger) return;
    const modal = document.querySelector(trigger.dataset.pcEdit);
    const form = modal && modal.querySelector("form");
    if (!form) return;
    const payload = JSON.parse(trigger.dataset.pcPayload || "{}");

    form.reset();
    if (payload.action) form.action = payload.action;
    fillForm(form, payload.fields);
    const title = modal.querySelector("[data-title]");
    const subtitle = modal.querySelector("[data-subtitle]");
    if (title && payload.title !== undefined) title.textContent = payload.title;
    if (subtitle && payload.subtitle !== undefined) subtitle.textContent = payload.subtitle;
    clearFormErrors(form);
    modal.dispatchEvent(new CustomEvent("pc:filled", { detail: payload }));
    pcModal.open(modal.id);
  });

  /* Add dialogs start empty: pc:open doesn't bubble, so listen in the capture phase */
  document.addEventListener("pc:open", (e) => {
    const modal = e.target;
    if (!(modal instanceof Element) || !modal.matches(".pc-modal[data-pc-reset]")) return;
    const form = modal.querySelector("form");
    if (!form) return;
    form.reset();
    clearFormErrors(form);
  }, true);

  /* Confirm before acting:
   *   <a href="/somewhere" data-pc-confirm="Leave this page?" data-pc-confirm-text="..." data-pc-confirm-icon="warning">
   * Links are followed and submit buttons submit their form after confirmation; other elements get a `pc:confirmed` event. */
  document.addEventListener("click", (e) => {
    const el = e.target.closest("[data-pc-confirm]");
    if (!el || el.dataset.pcConfirmed === "true") return;
    e.preventDefault();
    e.stopImmediatePropagation();
    pcDialog
      .confirm({
        title: el.getAttribute("data-pc-confirm"),
        text: el.getAttribute("data-pc-confirm-text") || "",
        icon: el.getAttribute("data-pc-confirm-icon") || "question",
        confirmButtonText: el.getAttribute("data-pc-confirm-button") || "Confirm",
      })
      .then(({ isConfirmed }) => {
        if (!isConfirmed) return;
        if (el.tagName === "A" && el.href) window.location.href = el.href;
        else if (el.type === "submit" && el.form) {
          if (window.pcDropdown) pcDropdown.closeAll();
          el.form.requestSubmit(el); // runs the form's onsubmit (e.g. submitForm) as a normal submit would
        } else el.dispatchEvent(new CustomEvent("pc:confirmed", { bubbles: true }));
      });
  }, true);

  /* Flashed messages: redirect()->with("toast", "...") renders <meta name="pc-toast"> in the layout */
  document.addEventListener("DOMContentLoaded", () => {
    const flash = document.querySelector('meta[name="pc-toast"]');
    if (flash && flash.content) toast[flash.dataset.type || "success"]({ message: flash.content, duration: 3200 });
  });
})();
