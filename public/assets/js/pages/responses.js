/**
 * The responses table (app/responses/index): a row opens its response in the side panel (#responsePanel),
 * fetched from FormResponsesController@show and drawn read-only in the form from #responsesConfig, every page at once,
 * flat on the panel in the Minimal style (panelless) with the form's palette and the app's light / dark mode. The
 * panel steps to the newer / older response and archives (or restores) it. ?response=<hash> keeps the open one in the URL, so a link or a reload opens it.
 */
(function () {
  const panelId = "responsePanel";
  const panel = document.getElementById(panelId);
  const configEl = document.getElementById("responsesConfig");
  if (!panel || !configEl || !window.surveyjs?.Model) return;

  const config = JSON.parse(configEl.textContent);
  const host = document.getElementById("responseView");
  const archiveForm = panel.querySelector("[data-response-archive]");
  let current = null;
  let request = null;

  const survey = new surveyjs.Model(config.content);
  survey.mode = "display";
  survey.questionsOnPageMode = "singlePage";
  survey.showNavigationButtons = false;
  survey.showTitle = false;
  // the phone layout (matrices stacked) while the panel is narrow, tables once it's expanded: set from the panel's own
  // width when it settles; SurveyJS's check, run mid-slide, would pick the wrong one
  survey.processResponsiveness = () => false;
  let rendered = false;

  // files kept in storage ("upload:{hash}") show through the app, which checks access and passes them on to a
  // short-lived signed link; those kept in the answer (signatures, older responses) show as they are
  const fromStorage = (question) => { if (question.getType() === "file") question.storeDataAsText = false; };
  survey.getAllQuestions(true).forEach((question) => {
    fromStorage(question);
    if (question.getType() === "paneldynamic") question.template.questions.forEach(fromStorage);
  });
  survey.onDynamicPanelAdded.add((_, options) => options.panel?.questions.forEach(fromStorage));
  survey.onDownloadFile.add((_, options) => {
    const upload = /^upload:([0-9a-f]{20})$/.exec(options.content || "");
    options.callback("success", upload && current
      ? config.uploadUrl.replace("__response__", current.hash).replace("__upload__", upload[1])
      : options.content);
  });

  /** The form's palette in the app's current mode. */
  function applyTheme() {
    const mode = document.documentElement.dataset.pcTheme === "dark" ? "dark" : "light";
    survey.applyTheme(surveyjs.buildTheme({ ...config.theme, style: "minimal", layout: "panelless", mode, backgroundImage: null }));
  }
  applyTheme();
  document.addEventListener("pc:theme", applyTheme);

  /** Keeps ?response= in step with the open response (null: none). */
  function remember(hash) {
    const url = new URL(location.href);
    if (hash) url.searchParams.set("response", hash);
    else url.searchParams.delete("response");
    history.replaceState(history.state, "", url);
  }


  function fill(response) {
    current = response;
    panel.querySelector("[data-title]").textContent = response.reference;
    panel.querySelector("[data-subtitle]").textContent = response.submitted;

    survey.data = response.data;
    if (!rendered) {
      surveyjs.renderSurvey(survey, host);
      rendered = true;
      new ResizeObserver(() => survey.setIsMobile(host.clientWidth < 600)).observe(host);
    }

    panel.querySelectorAll("[data-response-step]").forEach((button) => {
      button.disabled = !response[button.dataset.responseStep];
    });

    if (archiveForm) {
      archiveForm.querySelector('[name="responses[]"]').value = response.hash;
      archiveForm.querySelector('[name="archive"]').value = response.archived ? "0" : "1";
      archiveForm.querySelector("[data-archive-label]").textContent = response.archived ? pc.t("Restore") : pc.t("Archive");
      archiveForm.querySelector("[data-archive-icon]").className = `fa-regular ${response.archived ? "fa-arrow-rotate-left" : "fa-box-archive"}`;
      // archiving is the cautious one (warning outline); restoring is plain
      const button = archiveForm.querySelector("[data-archive-button]");
      button.classList.toggle("pc-btn-warning", !response.archived);
      button.classList.toggle("pc-btn-secondary", response.archived);
    }

    // the open row stands out in the table
    document.querySelectorAll("tr[data-pc-response]").forEach((row) => {
      row.toggleAttribute("data-pc-current", row.dataset.pcResponse === response.hash);
    });
  }

  /** Fetches the response and shows it in the panel. */
  async function open(hash) {
    if (request) request.abort();
    request = new AbortController();
    panel.setAttribute("aria-busy", "true");
    if (!pcOffcanvas.isOpen(panelId)) pcOffcanvas.open(panelId);

    try {
      const response = await fetch(config.showUrl.replace("__response__", encodeURIComponent(hash)), {
        headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" },
        signal: request.signal,
      });
      const data = await response.json().catch(() => ({}));
      if (!response.ok || !data.success) throw new Error(data.message || pc.t("Couldn't load this response."));
      fill(data.response);
      remember(hash);
      panel.querySelector(".pc-offcanvas-body")?.scrollTo({ top: 0 });
    } catch (err) {
      if (err.name === "AbortError") return;
      toast.error({ message: err.message });
      pcOffcanvas.close(panelId);
    } finally {
      panel.removeAttribute("aria-busy");
    }
  }

  // a row opens its response: anywhere on it but its checkbox and other controls
  document.addEventListener("click", (e) => {
    const button = e.target.closest("[data-pc-response-open]");
    const row = e.target.closest("tr[data-pc-response]");
    if (!button && (!row || e.target.closest("input, a, label, .pc-col-select, .pc-dropdown"))) return;
    open((button || row).dataset.pcResponseOpen || row.dataset.pcResponse);
  });

  panel.addEventListener("click", (e) => {
    const step = e.target.closest("[data-response-step]");
    if (step && current?.[step.dataset.responseStep]) open(current[step.dataset.responseStep]);
  });

  panel.addEventListener("pc:close", () => {
    if (request) request.abort();
    current = null;
    remember(null);
    document.querySelectorAll("tr[data-pc-current]").forEach((row) => row.removeAttribute("data-pc-current"));
  });

  const initial = new URL(location.href).searchParams.get("response");
  if (initial) open(initial);
})();

/**
 * Edge tabs on the responses table: when its columns run past the edge, a slim tab with a chevron sits on that edge
 * (the right one flush with the table's end, the left one against the pinned columns) and scrolls about a screen that
 * way. Each shows only while there's more to see in its direction. They're added here, and again when the list is
 * swapped in place (search, filters, paging).
 */
(function () {
  const STEP_MARGIN = 60;

  /** The tabs for a list's card, made once. */
  function tabs(card) {
    if (card.querySelector("[data-pc-edge-tab]")) return card.querySelectorAll("[data-pc-edge-tab]");
    ["start", "end"].forEach((side) => {
      const button = document.createElement("button");
      button.type = "button";
      button.className = `pc-edge-tab pc-edge-tab-${side}`;
      button.dataset.pcEdgeTab = side;
      button.setAttribute("aria-label", side === "end" ? pc.t("Scroll right") : pc.t("Scroll left"));
      button.innerHTML = `<i class="fa-regular fa-chevron-${side === "end" ? "right" : "left"} rtl:-scale-x-100" aria-hidden="true"></i>`;
      card.append(button);
    });
    return card.querySelectorAll("[data-pc-edge-tab]");
  }

  /** Places and shows / hides one card's tabs from its scroll position. */
  function sync(card) {
    const scroller = card.querySelector(".pc-list-scroll");
    const body = scroller?.querySelector("tbody");
    if (!body) return;
    const [start, end] = tabs(card);
    const box = card.getBoundingClientRect();
    const view = scroller.getBoundingClientRect();
    const rows = body.getBoundingClientRect();

    // centred on the rows in view; the start tab sits just past the pinned columns
    const top = Math.max(rows.top, view.top);
    const bottom = Math.min(rows.bottom, view.bottom);
    const middle = (top + bottom) / 2 - box.top;
    const pinned = scroller.querySelector("tbody .pc-col-pin");
    [start, end].forEach((tab) => { tab.style.top = `${middle - tab.offsetHeight / 2}px`; });
    if (pinned) start.style.insetInlineStart = `${pinned.getBoundingClientRect().right - box.left}px`;

    const scrolled = Math.abs(scroller.scrollLeft);
    const max = scroller.scrollWidth - scroller.clientWidth - 1;
    start.toggleAttribute("data-off", scrolled <= 1);
    end.toggleAttribute("data-off", scrolled >= max);
  }

  function syncAll() {
    document.querySelectorAll(".pc-responses-table").forEach(sync);
  }

  document.addEventListener("click", (e) => {
    const tab = e.target.closest("[data-pc-edge-tab]");
    if (!tab) return;
    const card = tab.closest(".pc-responses-table");
    const scroller = card.querySelector(".pc-list-scroll");
    const pinned = scroller.querySelector("tbody .pc-col-pin")?.getBoundingClientRect().right ?? 0;
    const step = Math.max(160, scroller.getBoundingClientRect().right - pinned - STEP_MARGIN);
    const rtl = getComputedStyle(scroller).direction === "rtl";
    scroller.scrollBy({ left: (tab.dataset.pcEdgeTab === "end") !== rtl ? step : -step });
  });

  // scroll doesn't bubble; capture it from the table (and the page), and follow size changes and list swaps
  document.addEventListener("scroll", syncAll, true);
  window.addEventListener("resize", syncAll);
  const list = document.querySelector("[data-pc-list]")?.parentElement;
  if (list) new MutationObserver(syncAll).observe(list, { childList: true, subtree: true });
  syncAll();
})();
