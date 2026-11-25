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
      toast.error({ message: pc.t("Your session expired. Reload the page and try again.") });
      return;
    }

    if (responseHandler && typeof responseHandler === "function") {
      responseHandler(data, response);
      return;
    }

    if (response.ok && data.success) {
      if (data.message) toast.success({ message: data.message });
      if (data.redirect) {
        setTimeout(() => {
          window.pcPageLoader?.show();
          window.location.href = data.redirect;
        }, 800);
      }
    } else {
      toast.error({ message: data.message || pc.t("An unknown error occurred.") });
    }
  } catch (err) {
    toast.error({ message: pc.t("Couldn't reach the server. Check your connection and try again.") });
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
  toast.info({ message: pc.t("Copied to clipboard") });
}

/** Placeholder for links / buttons whose feature isn't built yet: onclick="underDevelopment(event)". */
function underDevelopment(event) {
  if (event) event.preventDefault();
  pcDialog.alert({
    title: pc.t("Under development"),
    text: pc.t("This feature is under development and will be available soon."),
    icon: "info",
    confirmButtonText: pc.t("OK"),
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
  // Shared edit dialogs: one modal per page, filled from the clicked row.
  //   <button data-pc-edit="#editModule" data-pc-payload='{"action": "...", "title": "...", "subtitle": "...", "fields": {...}}'>
  // The modal's form posts to `action`; [data-title] / [data-subtitle] in the modal take `title` / `subtitle`.
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

  // Add dialogs start empty: pc:open doesn't bubble, so listen in the capture phase
  document.addEventListener("pc:open", (e) => {
    const modal = e.target;
    if (!(modal instanceof Element) || !modal.matches(".pc-modal[data-pc-reset]")) return;
    const form = modal.querySelector("form");
    if (!form) return;
    form.reset();
    clearFormErrors(form);
  }, true);

  // Confirm before acting:
  //   <a href="/somewhere" data-pc-confirm="Leave this page?" data-pc-confirm-text="..." data-pc-confirm-icon="warning">
  // Links are followed and submit buttons submit their form after confirmation; other elements get a `pc:confirmed` event.
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
        confirmButtonText: el.getAttribute("data-pc-confirm-button") || pc.t("Confirm"),
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

  // Flashed messages: redirect()->with("toast", "...") renders <meta name="pc-toast"> in the layout
  document.addEventListener("DOMContentLoaded", () => {
    const flash = document.querySelector('meta[name="pc-toast"]');
    if (flash && flash.content) toast[flash.dataset.type || "success"]({ message: flash.content, duration: 3200 });
  });

  /*
   * List toolbars (x-table search / filters slots): below sm the funnel button opens the filters panel under the row;
   * it carries a dot while any filter is set, so it isn't missed with the panel shut.
   */
  document.addEventListener("click", (e) => {
    const toggle = e.target.closest('[data-pc-trigger="toolbar-filters"]');
    if (!toggle) return;
    const toolbar = toggle.closest("[data-pc-toolbar]");
    const open = toolbar.getAttribute("data-pc-open") !== "true";
    toolbar.setAttribute("data-pc-open", String(open));
    toggle.setAttribute("aria-expanded", String(open));
    if (open) toolbar.querySelector(".pc-toolbar-filters :is(input, select, button, a)")?.focus();
  });

  /** Marks each funnel button that has a filter set. */
  function syncToolbars(root) {
    pc.qsa('[data-pc-trigger="toolbar-filters"]', root).forEach((toggle) => {
      const filters = document.getElementById(toggle.getAttribute("aria-controls"));
      const set = filters && (pc.qsa("select, input:not([type=hidden])", filters).some((field) => field.value !== "")
        || !!filters.querySelector('.pc-tab-active:not(:first-child)'));
      toggle.toggleAttribute("data-pc-active", !!set);
    });
  }
  syncToolbars(document);

  /*
   * Tables on phones: rows with .pc-col-more cells (hidden below sm) open like an accordion. Tapping the row (or Enter /
   * Space on it) slides a details row open beneath, listing those cells under their column headings. Links, buttons,
   * forms and menus in the row keep working as usual.
   */
  const phone = window.matchMedia("(max-width: 639.98px)");

  /** The row's details row, built on first use. */
  function detailsRow(row) {
    const next = row.nextElementSibling;
    if (next && next.classList.contains("pc-row-details")) return next;

    const heads = pc.qsa("thead th", row.closest("table"));
    const cells = Array.from(row.children);
    const more = cells.filter((cell) => cell.classList.contains("pc-col-more"));
    const items = more
      .map((cell) => `<dt>${pc.escapeHtml(heads[cells.indexOf(cell)]?.textContent.trim() || "")}</dt>`
        + `<dd class="${cell.className.replace("pc-col-more", "")}">${cell.innerHTML}</dd>`)
      .join("");
    const tr = document.createElement("tr");
    tr.className = "pc-row-details";
    tr.innerHTML = `<td colspan="${cells.length - more.length - cells.filter((c) => c.classList.contains("pc-col-hide")).length}">`
      + `<div class="pc-row-details-inner"><div><dl>${items}</dl></div></div></td>`;
    row.after(tr);
    tr.offsetHeight; // lay it out shut first, so opening slides
    return tr;
  }

  /** Opens or closes a row's details. */
  function toggleRow(row) {
    const open = row.getAttribute("aria-expanded") !== "true";
    row.setAttribute("aria-expanded", String(open));
    detailsRow(row).setAttribute("data-pc-open", String(open));
  }

  /** Expandable rows are focusable and announce their state only where they expand (phones). */
  function syncExpandableRows() {
    pc.qsa(".pc-table tbody tr").forEach((row) => {
      if (!row.querySelector(":scope > td.pc-col-more")) return;
      row.setAttribute("data-pc-expandable", "");
      if (phone.matches) {
        row.tabIndex = 0;
        if (!row.hasAttribute("aria-expanded")) row.setAttribute("aria-expanded", "false");
      } else {
        row.removeAttribute("tabindex");
        row.removeAttribute("aria-expanded");
        const details = row.nextElementSibling;
        if (details && details.classList.contains("pc-row-details")) details.setAttribute("data-pc-open", "false");
      }
    });
  }
  syncExpandableRows();
  phone.addEventListener("change", syncExpandableRows);

  document.addEventListener("click", (e) => {
    const row = e.target.closest("tr[data-pc-expandable]");
    if (!row || !phone.matches || e.target.closest("a, button, input, select, textarea, label, form, .pc-dropdown")) return;
    toggleRow(row);
  });
  document.addEventListener("keydown", (e) => {
    if ((e.key !== "Enter" && e.key !== " ") || !phone.matches || !e.target.matches("tr[data-pc-expandable]")) return;
    e.preventDefault();
    toggleRow(e.target);
  });

  /*
   * Lists update in place: inside an x-table list ([data-pc-list]) the search (as you type), filters, tabs, page links
   * and other links to this same page fetch that page and swap only the list; the URL follows (history), so reload,
   * sharing and back / forward still work. Anything unexpected falls back to a normal page load.
   */
  let listRequest = null;

  /** The list's position on the page, to find its counterpart in the fetched page. */
  function listIndex(list) {
    return pc.qsa("[data-pc-list]").indexOf(list);
  }

  /** Fetches `url` and swaps the list in; `push` adds a history entry (not when answering back / forward). */
  async function loadList(list, url, push) {
    const index = listIndex(list);
    if (listRequest) listRequest.abort();
    listRequest = new AbortController();
    list.setAttribute("aria-busy", "true");

    try {
      const response = await fetch(url, { headers: { Accept: "text/html" }, signal: listRequest.signal });
      if (!response.ok || new URL(response.url).pathname !== new URL(url, location.href).pathname) throw new Error("page");
      const page = new DOMParser().parseFromString(await response.text(), "text/html");
      const next = page.querySelectorAll("[data-pc-list]")[index];
      if (!next) throw new Error("page");

      // keep what the user is in the middle of: the filters panel left open, the field being typed in
      const open = list.querySelector("[data-pc-toolbar]")?.getAttribute("data-pc-open") === "true";
      const active = list.contains(document.activeElement) ? document.activeElement : null;
      const caret = active && "selectionStart" in active ? [active.selectionStart, active.selectionEnd] : null;

      const current = pc.qsa("[data-pc-list]")[index];
      current.replaceWith(next);
      if (push) history.pushState({ pcList: true }, "", url);
      document.title = page.title || document.title;

      if (open) {
        next.querySelector("[data-pc-toolbar]")?.setAttribute("data-pc-open", "true");
        next.querySelector('[data-pc-trigger="toolbar-filters"]')?.setAttribute("aria-expanded", "true");
      }
      if (active && active.name) {
        const again = next.querySelector(`[name="${CSS.escape(active.name)}"]:not([type=hidden])`);
        if (again) {
          again.focus();
          if (caret && "setSelectionRange" in again) {
            try { again.setSelectionRange(caret[0], caret[1]); } catch (err) { /* not a text field */ }
          }
        }
      }
      syncToolbars(next);
      syncExpandableRows();
    } catch (err) {
      if (err.name === "AbortError") return;
      location.href = url;
    } finally {
      list.removeAttribute("aria-busy");
    }
  }

  /** The GET form's target URL with its fields, leaving empty ones out. */
  function formUrl(form) {
    const url = new URL(form.getAttribute("action") || location.pathname, location.href);
    url.search = "";
    new FormData(form).forEach((value, key) => {
      if (value !== "") url.searchParams.append(key, value);
    });
    return url.toString();
  }

  document.addEventListener("submit", (e) => {
    const form = e.target;
    const list = form.closest("[data-pc-list]");
    if (!list || (form.getAttribute("method") || "get").toLowerCase() !== "get" || form.hasAttribute("data-pc-no-ajax")) return;
    e.preventDefault();
    loadList(list, formUrl(form), true);
  });

  // search as you type, a beat after the last key
  const typing = pc.debounce
    ? pc.debounce((form) => form.requestSubmit(), 350)
    : (() => { let t; return (form) => { clearTimeout(t); t = setTimeout(() => form.requestSubmit(), 350); }; })();
  document.addEventListener("input", (e) => {
    const input = e.target;
    if (!input.matches('[data-pc-list] form input[type="search"]')) return;
    typing(input.form);
  });

  document.addEventListener("click", (e) => {
    const link = e.target.closest("[data-pc-list] a[href]");
    if (!link || e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
    if (link.target && link.target !== "_self") return;
    if (link.hasAttribute("download") || link.hasAttribute("data-pc-no-ajax") || link.closest(".pc-dropdown")) return;
    const url = new URL(link.href, location.href);
    if (url.origin !== location.origin || url.pathname !== location.pathname) return;
    e.preventDefault();
    loadList(link.closest("[data-pc-list]"), url.toString(), true);
  });

  if (document.querySelector("[data-pc-list]")) {
    history.replaceState({ pcList: true }, "");
    window.addEventListener("popstate", (e) => {
      const list = document.querySelector("[data-pc-list]");
      if (e.state && e.state.pcList && list) loadList(list, location.href, false);
    });
  }
})();
