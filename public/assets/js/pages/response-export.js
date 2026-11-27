/**
 * The export options (app/responses/partials/export): opened from the toolbar it exports what the table lists, from
 * the bulk menu ([data-export-ticked]) the ticked rows (sent as responses[]). The table's view, filters, search and
 * order go along in the query string, read as the form is sent (the list updates in place, so the URL is the truth).
 * A plain export downloads (a native submit); one with attachments is posted (submitForm) and its message toasted,
 * as it's prepared in the background and emailed. Export attachments ([data-export-attachments-run], toolbar or bulk
 * menu) posts the hidden attachments form the same way: what the table lists, or the ticked rows.
 */
(function () {
  const modal = document.querySelector("[data-export-form]");
  const form = modal?.querySelector("form");
  if (!form) return;

  const base = form.getAttribute("action");
  const selection = modal.querySelector("[data-export-selection]");
  const subtitle = modal.querySelector("[data-subtitle]");
  // this form decides per submit how it's sent (below)
  form.removeAttribute("onsubmit");

  /** Lists the ticked rows (or none: all listed) and says which in the subtitle. */
  function choose(hashes) {
    selection.replaceChildren(...hashes.map((hash) => Object.assign(document.createElement("input"), { type: "hidden", name: "responses[]", value: hash })));
    subtitle.textContent = !hashes.length
      ? modal.dataset.exportAll
      : hashes.length === 1 ? modal.dataset.exportSelectedOne : modal.dataset.exportSelected.replace(":count", hashes.length);
  }

  /** The page's query (view, filters, search, order) for an export's action. */
  function query() {
    const params = new URLSearchParams(location.search);
    params.delete("response");
    params.delete("page");
    return params.toString() ? `?${params}` : "";
  }

  // just the attachments: from the bulk menu the ticked rows, from the toolbar all listed
  const attachments = document.querySelector("[data-export-attachments-form]");
  const attachmentsBase = attachments?.getAttribute("action");
  attachments?.addEventListener("submit", (e) => submitForm(e));

  document.addEventListener("click", (e) => {
    const run = e.target.closest("[data-export-attachments-run]");
    if (run && attachments) {
      const list = run.closest("[data-pc-list]");
      const ticked = run.closest(".pc-list-bulk") ? pc.qsa("[data-pc-select]:checked", list).map((box) => box.value) : [];
      pc.qsa('input[name="responses[]"]', attachments).forEach((input) => input.remove());
      ticked.forEach((hash) => attachments.append(Object.assign(document.createElement("input"), { type: "hidden", name: "responses[]", value: hash })));
      attachments.action = attachmentsBase + query();
      attachments.requestSubmit();
      return;
    }
    if (e.target.closest('[data-pc-target="#exportResponses"]')) choose([]);
    if (e.target.closest("[data-export-ticked]")) {
      const list = e.target.closest("[data-pc-list]");
      choose(pc.qsa("[data-pc-select]:checked", list).map((box) => box.value));
      pcModal.open("exportResponses");
    }
  });

  form.addEventListener("submit", (e) => {
    form.action = base + query();

    if (form.querySelector("[data-export-attachments]")?.checked) {
      submitForm(e, (data, response) => {
        if (!response.ok || !data.success) {
          toast.error({ message: data.message || pc.t("An unknown error occurred.") });
          return;
        }
        toast.success({ message: data.message });
        pcModal.close("exportResponses");
      });
      return;
    }
    // the download leaves the page as it is
    setTimeout(() => pcModal.close("exportResponses"), 0);
  });
})();
