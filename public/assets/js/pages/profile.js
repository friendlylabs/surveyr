/**
 * pages/profile.js — the profile page's security sections (app/profile/index): two-factor authentication, browser
 * sessions and passkeys. Endpoints come from #profilePage's data-* attributes.
 *
 * Every one of them sits behind Laravel's password.confirm middleware, which answers 423 once the confirmation
 * has lapsed (auth.password_timeout). guarded(action) runs an action; on a 423 it opens #confirmPassword and runs
 * the same action again once the password is confirmed, so the user never leaves the page.
 * Profile information and password are plain submitForm() forms and need nothing here.
 */
(function () {
  const page = document.getElementById("profilePage");
  if (!page) return;
  const urls = page.dataset;

  /* ---------- password confirmation ---------- */
  let pending = null;
  const confirmModal = document.getElementById("confirmPassword");
  const confirmForm = confirmModal.querySelector("form");
  confirmForm.removeAttribute("onsubmit");
  confirmForm.addEventListener("submit", (e) =>
    submitForm(e, (data, response) => {
      if (!(response.ok && data.success)) return toast.error({ message: data.message });
      const retry = pending; // before close: pc:close drops the pending action
      pcModal.close("confirmPassword");
      if (retry) retry();
    })
  );
  confirmModal.addEventListener("pc:close", () => (pending = null));

  class ConfirmRequired extends Error {}

  /** Fetch that turns a 423 into ConfirmRequired; anything else not ok becomes an Error with the server's message. */
  async function call(url, options = {}) {
    const res = await pc.jsonFetch(url, options);
    if (res.status === 423) throw new ConfirmRequired();
    const data = await res.json().catch(() => ({}));
    if (!res.ok) {
      const err = new Error(data.message || pc.t("Something went wrong. Try again."));
      err.data = data;
      throw err;
    }
    return data;
  }

  /** Run action(); a 423 asks for the password, then runs it again. button (optional) shows the loading state. */
  async function guarded(action, button) {
    if (button) buttonState(button, "loading");
    try {
      return await action();
    } catch (err) {
      if (err instanceof ConfirmRequired || err.confirmRequired) {
        pending = () => guarded(action, button);
        pcModal.open("confirmPassword");
        return;
      }
      if (err.name === "NotAllowedError" || err.name === "AbortError") return; // passkey prompt dismissed
      toast.error({ message: err.message });
    } finally {
      if (button && document.body.contains(button)) buttonState(button, "reset");
    }
  }

  const confirm = (options) => pcDialog.confirm(options).then(({ isConfirmed }) => isConfirmed);

  /* ---------- two-factor authentication ---------- */
  const twoFactor = document.getElementById("twoFactor");
  if (twoFactor) {
    const qr = document.getElementById("twoFactorQr");
    const secret = document.getElementById("twoFactorSecret");
    const codes = document.getElementById("twoFactorCodes");
    const codeForm = document.getElementById("twoFactorConfirmForm");
    const copyCodes = twoFactor.querySelector('[data-two-factor-action="copy-codes"]');

    function show(state) {
      twoFactor.dataset.twoFactorState = state;
      pc.qsa("[data-two-factor-view]", twoFactor).forEach((el) => (el.hidden = el.dataset.twoFactorView !== state));
      twoFactor.querySelector('[data-two-factor-badge="enabled"]').hidden = state !== "enabled";
      twoFactor.querySelector('[data-two-factor-badge="off"]').hidden = state === "enabled";
      if (state !== "enabled") {
        codes.hidden = true;
        copyCodes.hidden = true;
      }
    }

    async function loadSetup() {
      const [image, key] = await Promise.all([call(urls.twoFactorQr), call(urls.twoFactorSecret)]);
      qr.innerHTML = image.svg || "";
      secret.textContent = key.secretKey || "";
      codeForm.elements.code.focus();
    }

    async function loadCodes() {
      const list = await call(urls.twoFactorCodes);
      codes.innerHTML = list.map((code) => `<span>${pc.escapeHtml(code)}</span>`).join("");
      codes.hidden = false;
      copyCodes.hidden = false;
    }

    const actions = {
      enable: (button) =>
        guarded(async () => {
          await call(urls.twoFactorEnable, { method: "POST" });
          show("pending");
          await loadSetup();
        }, button),

      cancel: async (button) => {
        if (!(await confirm({ title: pc.t("Cancel the setup?"), text: pc.t("You can start again whenever you like."), icon: "warning", confirmButtonText: pc.t("Cancel setup"), cancelButtonText: pc.t("Keep going") }))) return;
        guarded(async () => {
          await call(urls.twoFactorDisable, { method: "POST" });
          qr.innerHTML = '<span class="pc-spinner text-pc-muted"></span>';
          secret.textContent = "…";
          codeForm.reset();
          show("disabled");
        }, button);
      },

      codes: (button) => guarded(loadCodes, button),

      "copy-codes": () => copyToClipboard(codes.innerText),

      regenerate: async (button) => {
        if (!(await confirm({ title: pc.t("Make new recovery codes?"), text: pc.t("The old ones stop working straight away."), icon: "warning", confirmButtonText: pc.t("Make new codes") }))) return;
        guarded(async () => {
          await call(urls.twoFactorRegenerate, { method: "POST" });
          await loadCodes();
          toast.success({ message: pc.t("New recovery codes made. Save them somewhere safe.") });
        }, button);
      },

      disable: async (button) => {
        if (!(await confirm({ title: pc.t("Turn off two-factor authentication?"), text: pc.t("Your account will be protected by your password alone."), icon: "danger", confirmButtonText: pc.t("Turn off") }))) return;
        guarded(async () => {
          await call(urls.twoFactorDisable, { method: "POST" });
          show("disabled");
          toast.success({ message: pc.t("Two-factor authentication is off") });
        }, button);
      },
    };

    twoFactor.addEventListener("click", (e) => {
      const button = e.target.closest("[data-two-factor-action]");
      if (button) actions[button.dataset.twoFactorAction](button);
    });

    codeForm.addEventListener("submit", (e) => {
      e.preventDefault();
      const button = codeForm.querySelector('button[type="submit"]');
      clearFormErrors(codeForm);
      guarded(async () => {
        try {
          await call(urls.twoFactorConfirm, { method: "POST", body: JSON.stringify({ code: codeForm.elements.code.value.trim() }) });
        } catch (err) {
          const message = err.data?.errors?.code?.[0];
          if (!message) throw err;
          fieldError(codeForm, "code", message);
          return codeForm.elements.code.focus();
        }
        show("enabled");
        await loadCodes();
        toast.success({ message: pc.t("Two-factor authentication is on. Save your recovery codes.") });
      }, button);
    });

    // set up but never confirmed (a reload mid-setup): pick the setup back up
    if (twoFactor.dataset.twoFactorState === "pending") guarded(loadSetup);
  }

  /* ---------- browser sessions ---------- */
  const sessions = document.getElementById("sessions");
  sessions?.addEventListener("click", async (e) => {
    const button = e.target.closest("[data-session-action]");
    if (!button) return;

    if (button.dataset.sessionAction === "others") {
      if (!(await confirm({ title: pc.t("Log out your other devices?"), text: pc.t("Every other browser signed in to your account is signed out now."), icon: "warning", confirmButtonText: pc.t("Log them out") }))) return;
      return guarded(async () => {
        const data = await call(urls.sessionDestroyOthers, { method: "POST" });
        toast.success({ message: data.message });
        if (data.redirect) setTimeout(() => (location.href = data.redirect), 800);
      }, button);
    }

    if (!(await confirm({ title: pc.t("Log out this device?"), text: pc.t("It's signed out straight away."), icon: "warning", confirmButtonText: pc.t("Log out") }))) return;
    guarded(async () => {
      const data = await call(urls.sessionDestroy.replace("__ID__", encodeURIComponent(button.dataset.sessionId)), { method: "POST" });
      button.closest("[data-session-row]").remove();
      toast.success({ message: data.message });
    }, button);
  });

  /* ---------- passkeys ---------- */
  const passkeyForm = document.getElementById("passkeyForm");
  if (passkeyForm) {
    const list = document.getElementById("passkeyList");
    const empty = document.getElementById("passkeyEmpty");

    // the add form only exists where the browser can make a passkey; existing ones can still be removed anywhere
    const supported = !!(window.pcWebAuthn && pcWebAuthn.supported());
    passkeyForm.hidden = !supported;
    document.getElementById("passkeyUnsupported").hidden = supported;

    function row(passkey) {
      const li = document.createElement("li");
      li.className = "pc-section-row";
      li.dataset.passkeyRow = "";
      li.innerHTML = `
        <span class="pc-empty-icon mb-0 size-9 flex-none"><i class="ph ph-fingerprint text-lg" aria-hidden="true"></i></span>
        <div class="min-w-0 flex-1">
          <div class="truncate text-[13px] font-medium">${pc.escapeHtml(passkey.name)}</div>
          <div class="font-pc-mono text-[11px] text-pc-muted">${pc.escapeHtml(pc.t("Added just now · not used yet"))}</div>
        </div>
        <button type="button" class="pc-btn pc-btn-ghost pc-btn-icon pc-btn-sm text-pc-danger-strong" aria-label="${pc.escapeHtml(pc.t("Remove :name", { name: passkey.name }))}" data-pc-tooltip="${pc.escapeHtml(pc.t("Remove"))}" data-passkey-action="remove" data-passkey-id="${pc.escapeHtml(passkey.id)}"><i class="ph ph-trash text-base" aria-hidden="true"></i></button>`;
      return li;
    }

    passkeyForm.addEventListener("submit", (e) => {
      e.preventDefault();
      const name = passkeyForm.elements.name.value.trim() || pc.t("Passkey");
      guarded(async () => {
        const passkey = await pcWebAuthn.register(urls.passkeyOptions, urls.passkeyStore, name);
        list.insertBefore(row(passkey), empty);
        empty.hidden = true;
        passkeyForm.reset();
        toast.success({ message: pc.t("Passkey added. You can sign in with it now.") });
      }, passkeyForm.querySelector('button[type="submit"]'));
    });

    list.addEventListener("click", async (e) => {
      const button = e.target.closest('[data-passkey-action="remove"]');
      if (!button) return;
      if (!(await confirm({ title: pc.t("Remove this passkey?"), text: pc.t("You won't be able to sign in with it any more."), icon: "danger", confirmButtonText: pc.t("Remove") }))) return;
      guarded(async () => {
        await call(urls.passkeyDestroy.replace("__ID__", encodeURIComponent(button.dataset.passkeyId)), { method: "POST" });
        button.closest("[data-passkey-row]").remove();
        empty.hidden = !!list.querySelector("[data-passkey-row]");
        toast.success({ message: pc.t("Passkey removed") });
      }, button);
    });
  }
})();
