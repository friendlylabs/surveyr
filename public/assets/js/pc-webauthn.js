/**
 * pc-webauthn.js — thin wrapper around the browser's WebAuthn API for
 * laravel/passkeys' JSON endpoints (Laravel\Passkeys\Http\Controllers\*).
 * Uses PublicKeyCredential.parseCreationOptionsFromJSON()/parseRequestOptionsFromJSON()
 * and credential.toJSON(), so option/credential payloads pass straight through
 * to webauthn-lib's JSON (de)serializer with no manual base64url handling.
 *
 * JS API:
 *   pcWebAuthn.supported() -> bool
 *   pcWebAuthn.register(optionsUrl, storeUrl, name) -> Promise<{status, id, name}>
 *   pcWebAuthn.login(optionsUrl, loginUrl, remember) -> Promise<{redirect}>
 * Both reject with an Error; a `confirmRequired: true` property means the
 * caller should confirm the password (a 423 from password.confirm) and try again.
 */
(function () {
  function supported() {
    return !!(
      window.PublicKeyCredential &&
      typeof PublicKeyCredential.parseCreationOptionsFromJSON === "function" &&
      typeof PublicKeyCredential.parseRequestOptionsFromJSON === "function"
    );
  }

  function confirmRequiredError() {
    const err = new Error(pc.t("Please confirm your password to continue."));
    err.confirmRequired = true;
    return err;
  }

  async function fetchOptions(url) {
    const res = await pc.jsonFetch(url);
    if (res.status === 423) throw confirmRequiredError();
    if (!res.ok) throw new Error(pc.t("Could not start the passkey ceremony."));
    const { options } = await res.json();
    return options;
  }

  async function postCredential(url, payload) {
    const res = await pc.jsonFetch(url, { method: "POST", body: JSON.stringify(payload) });
    if (res.status === 423) throw confirmRequiredError();
    const data = await res.json().catch(() => ({}));
    if (!res.ok) throw new Error(data.message || pc.t("The passkey request failed."));
    return data;
  }

  async function register(optionsUrl, storeUrl, name) {
    if (!supported()) throw new Error(pc.t("Passkeys aren't supported in this browser."));

    const options = await fetchOptions(optionsUrl);
    const credential = await navigator.credentials.create({
      publicKey: PublicKeyCredential.parseCreationOptionsFromJSON(options),
    });

    return postCredential(storeUrl, { name, credential: credential.toJSON() });
  }

  async function login(optionsUrl, loginUrl, remember) {
    if (!supported()) throw new Error(pc.t("Passkeys aren't supported in this browser."));

    const options = await fetchOptions(optionsUrl);
    const credential = await navigator.credentials.get({
      publicKey: PublicKeyCredential.parseRequestOptionsFromJSON(options),
    });

    return postCredential(loginUrl, { credential: credential.toJSON(), remember: !!remember });
  }

  window.pcWebAuthn = { supported, register, login };
})();
