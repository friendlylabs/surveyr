/**
 * Admin settings (admin/settings): the storage form shows only the chosen storage's line and fields (those of the
 * others are disabled, so they aren't sent), and Test connection posts the form to the test route instead of saving it.
 */
(function () {
  const form = document.querySelector("[data-storage-form]");
  if (!form) return;

  /** Shows the chosen storage's fields. */
  function sync() {
    const driver = form.querySelector("[data-storage-driver]")?.value;
    pc.qsa("[data-storage-hint]", form).forEach((hint) => { hint.hidden = hint.dataset.storageHint !== driver; });
    pc.qsa("[data-storage-fields]", form).forEach((fields) => {
      const on = fields.dataset.storageFields === driver;
      fields.hidden = !on;
      pc.qsa("input", fields).forEach((input) => { input.disabled = !on; });
    });
  }
  form.addEventListener("change", (e) => { if (e.target.matches("[data-storage-driver]")) sync(); });
  sync();

  // the same fields, checked without saving
  form.querySelector("[data-storage-test]")?.addEventListener("click", (e) => {
    const save = form.action;
    form.action = form.dataset.testUrl;
    submitForm({ preventDefault() {}, target: form, submitter: e.currentTarget });
    form.action = save;
  });
})();
