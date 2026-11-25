/**
 * auth.js — shared by the auth pages (login, register, forgot-password, check-email, reset-password, verify-email, email-link).
 *
 *   auth.param(name)             query-string value, or null
 *   auth.validate(form)          checks every [required] field, marks aria-invalid, shows the field's error
 *                                (the element named in its aria-describedby) and focuses the first bad one
 *   auth.carryEmail(form)        pre-fills the email field from ?email= and copies the typed email onto links
 *                                marked [data-auth-carry-email] so the next page opens with it filled in
 *   auth.submit(form, onError?)  validate, then post with submitForm(); a success follows data.redirect,
 *                                a failure goes to onError(data) or is toasted
 *
 * Extra rules: data-auth-match="#otherInput" (must equal), minlength (native).
 * Error text comes from data-auth-msg-<rule> on the input (missing, email, short, match) or a default.
 */
window.auth = (function () {
  const DEFAULTS = {
    missing: () => pc.t("This field is required."),
    email: () => pc.t("Enter a valid email address."),
    short: () => pc.t("Too short."),
    match: () => pc.t("Doesn't match."),
  };

  function param(name) {
    return new URLSearchParams(location.search).get(name);
  }

  function errorEl(input) {
    const id = (input.getAttribute("aria-describedby") || "").split(" ").find((i) => i.endsWith("Error"));
    return id ? document.getElementById(id) : null;
  }

  function problem(input) {
    const v = input.value.trim();
    if (!v) return "missing";
    if (input.type === "email" && input.validity.typeMismatch) return "email";
    if (input.minLength > 0 && input.value.length < input.minLength) return "short";
    const match = input.dataset.authMatch && document.querySelector(input.dataset.authMatch);
    if (match && input.value !== match.value) return "match";
    return null;
  }

  function mark(input) {
    const rule = problem(input);
    const err = errorEl(input);
    input.setAttribute("aria-invalid", String(!!rule));
    if (err) {
      err.hidden = !rule;
      if (rule) err.textContent = input.dataset["authMsg" + rule[0].toUpperCase() + rule.slice(1)] || DEFAULTS[rule]();
    }
    return !rule;
  }

  function validate(form) {
    const fields = pc.qsa("input[required]", form);
    const bad = fields.filter((f) => !mark(f));
    if (bad.length) bad[0].focus();
    return !bad.length;
  }

  // Once a field has been flagged, re-check it as the user types so the error clears.
  document.addEventListener("input", (e) => {
    const input = e.target.closest("input[aria-invalid='true']");
    if (input) mark(input);
  });

  function carryEmail(form) {
    const input = form.querySelector("input[type='email']");
    if (!input) return;
    const given = param("email");
    if (given && !input.value) input.value = given;
    document.addEventListener("click", (e) => {
      const link = e.target.closest("a[data-auth-carry-email]");
      if (!link || !input.value.trim()) return;
      const url = new URL(link.href, location.href);
      url.searchParams.set("email", input.value.trim());
      link.href = url.toString();
    });
  }

  function submit(form, onError = null) {
    form.addEventListener("submit", (e) => {
      if (!validate(form)) return e.preventDefault();
      submitForm(e, (data, response) => {
        if (response.ok && data.success) {
          if (data.redirect) window.location.href = data.redirect;
          else if (data.message) toast.success({ message: data.message });
        } else if (onError) {
          onError(data, response);
        } else {
          toast.error({ message: data.message || pc.t("Something went wrong. Try again.") });
        }
      });
    });
  }

  return { param, validate, carryEmail, submit };
})();
