/**
 * Visualize (app/responses/visualize), bundled with its engine (libs/surveyr_analytics) and the app's charts
 * (charts.js, window.pcCharts): draws the open tab's cards from #visualizeConfig. The engine keeps the visual each
 * chart shows (a viewer's switch, not saved); this script follows it: the chart pane or the table pane and the pressed
 * visual button. In the add / edit chart forms, the visuals offered follow the question picked.
 * Other texts page through ResponsesVisualizeController@answers; a chart downloads as a PNG. Printing (the Print
 * button, or the browser's own) lays the cards out in rows of two in the tab's order and redraws every chart at half
 * the printed page's width first, then puts the columns back (app.css @media print colours and breaks the pages).
 *
 * Masonry: the cards are dealt, in the tab's order, into as many columns as fit (each at least COLUMN_MIN wide),
 * every card to the column that's shortest so far, so the order reads across and the columns end close together.
 * The shorter columns then stretch to the tallest: each card grows by at most STRETCH_MAX, its body pushed down so
 * the charts line up along the bottom; a gap bigger than that is left, rather than cards turning into empty boxes.
 * A line across many points (more than WIDE_POINTS) is drawn wide: a row of its own with one column beside it (2:1
 * with three columns, 1:1 with two), the charts after it stacked in that column until they reach its height.
 */
import "./charts.js";
import { createAnalytics } from "../../libs/surveyr_analytics/src/index.js";

/** Sets the page up; does nothing on a page without #visualizeConfig. */
function visualize() {
  const configEl = document.getElementById("visualizeConfig");
  if (!configEl) return;

  const config = JSON.parse(configEl.textContent);
  const root = document.documentElement;
  const analytics = createAnalytics({
    summaries: config.summaries,
    strings: config.strings,
    locale: root.lang,
    rtl: root.dir === "rtl",
  });
  const cards = new Map([...document.querySelectorAll("[data-viz-card]")].map((card) => [card.dataset.vizCard, card]));
  const board = document.querySelector(".pc-viz");

  /** Options for a select: [value, text] pairs, `chosen` selected. */
  function options(select, pairs, chosen = "") {
    select.replaceChildren(...pairs.map(([value, text]) => {
      const option = document.createElement("option");
      option.value = value;
      option.textContent = text;
      option.selected = value === chosen;
      return option;
    }));
  }

  /** A visual's name, as the page's strings give it. */
  const visualName = (visual) => config.strings.visuals[visual] || visual;

  /** The question chart's "Show as": the default, then the visuals the picked question can take. */
  function fillVisuals(form, chosen = "") {
    const question = form.querySelector("[data-viz-chart-question]")?.value;
    const select = form.querySelector("[data-viz-chart-visual]");
    if (select) options(select, ["", ...(config.questionVisuals[question] || [])].map((visual) => [visual, visualName(visual)]), chosen);
  }

  /** The grouped chart's "Show as": what its mode and number of series allow (ChartRules::groupedVisuals). */
  function fillGroupedVisuals(form, chosen = "") {
    const select = form.querySelector("[data-viz-grouped-visual]");
    if (!select) return;
    const split = form.querySelector('[data-viz-chart-mode][value="split"]').checked;
    const rows = form.querySelectorAll("[data-viz-series-item]").length;
    const allowed = config.groupedVisuals[split ? "split" : rows > 1 ? "multi" : "single"];
    options(select, allowed.map((visual) => [visual, visualName(visual)]), allowed.includes(chosen) ? chosen : allowed[0]);
  }

  /** Numbers the series rows' fields (series[0][question], ...) and caps them at the most a chart takes. */
  function numberSeries(form) {
    const rows = [...form.querySelectorAll("[data-viz-series-item]")];
    rows.forEach((row, index) => {
      row.querySelector("[data-viz-series-question]").name = `series[${index}][question]`;
      row.querySelector("[data-viz-series-aggregate]").name = `series[${index}][aggregate]`;
      row.querySelector("[data-viz-series-remove]").hidden = rows.length < 2;
    });
    form.querySelector("[data-viz-series-add]").hidden = rows.length >= config.maxSeries;
  }

  /** Adds a number row, filled with `series` when given. */
  function addSeries(form, series = {}) {
    const row = form.querySelector("[data-viz-series-row]").content.firstElementChild.cloneNode(true);
    row.querySelector("[data-viz-series-question]").value = series.question || "";
    row.querySelector("[data-viz-series-aggregate]").value = series.aggregate || "sum";
    form.querySelector("[data-viz-series]").append(row);
    numberSeries(form);
  }

  /** Shows one kind's fields and one mode's, disabling the others so they aren't posted; the chips mark the choice. */
  function syncKind(form) {
    form.querySelectorAll("[data-viz-chart-kind], [data-viz-chart-mode]").forEach((radio) => radio.closest(".pc-tab")?.classList.toggle("pc-tab-active", radio.checked));
    const kind = form.querySelector("[data-viz-chart-kind]:checked")?.value || "question";
    form.querySelectorAll("[data-viz-kind]").forEach((fieldset) => {
      const on = fieldset.dataset.vizKind === kind;
      fieldset.hidden = !on;
      fieldset.disabled = !on;
    });
    const mode = form.querySelector("[data-viz-chart-mode]:checked")?.value || "numbers";
    form.querySelectorAll("[data-viz-mode]").forEach((block) => {
      const on = block.dataset.vizMode === mode;
      block.hidden = !on;
      block.querySelectorAll("select, input").forEach((field) => (field.disabled = !on));
    });
  }

  /** Puts the form back to a chart's fields (or empty, for a new chart). */
  function fillChart(form, fields = {}) {
    form.reset();
    window.clearFormErrors?.(form);
    const kind = fields.kind || "question";
    form.querySelector(`[data-viz-chart-kind][value="${kind}"]`).checked = true;
    form.querySelector(`[data-viz-chart-mode][value="${fields.mode || "numbers"}"]`).checked = true;
    form.querySelector("[data-viz-series]").replaceChildren();
    (fields.series?.length ? fields.series : [{}]).forEach((series) => addSeries(form, series));
    const own = form.querySelector(`[data-viz-kind="${kind}"]`);
    if (kind === "grouped") {
      form.querySelector('[name="label"]').value = fields.label || "";
      form.querySelector('[name="split"]').value = fields.split || "";
    } else {
      form.querySelector('[name="question"]').value = fields.question || "";
    }
    own.querySelector('[name="title"]').value = fields.title || "";
    syncKind(form);
    fillVisuals(form, kind === "question" ? fields.visual || "" : "");
    fillGroupedVisuals(form, kind === "grouped" ? fields.visual || "" : "");
  }

  document.querySelectorAll("[data-viz-chart-form]").forEach((modal) => {
    const form = modal.querySelector("form");
    if (!form) return;
    form.addEventListener("change", (event) => {
      if (event.target.matches("[data-viz-chart-question]")) fillVisuals(form);
      if (event.target.matches("[data-viz-chart-kind], [data-viz-chart-mode]")) {
        syncKind(form);
        fillGroupedVisuals(form, form.querySelector("[data-viz-grouped-visual]").value);
      }
    });
    form.addEventListener("click", (event) => {
      if (event.target.closest("[data-viz-series-add]")) {
        addSeries(form);
        fillGroupedVisuals(form, form.querySelector("[data-viz-grouped-visual]").value);
      }
      const remove = event.target.closest("[data-viz-series-remove]");
      if (remove) {
        remove.closest("[data-viz-series-item]").remove();
        numberSeries(form);
        fillGroupedVisuals(form, form.querySelector("[data-viz-grouped-visual]").value);
      }
    });
    // the add panel opens empty each time
    if (modal.hasAttribute("data-viz-chart-new")) modal.addEventListener("pc:open", () => fillChart(form));
    fillChart(form);
  });

  // a card's Edit: the edit panel posts to that chart, filled with its fields
  document.addEventListener("click", (event) => {
    const trigger = event.target.closest("[data-viz-chart-edit]");
    const panel = document.getElementById("updateChart");
    const form = panel?.querySelector("form");
    if (!trigger || !form) return;
    const payload = JSON.parse(trigger.dataset.vizPayload || "{}");
    form.action = payload.action;
    fillChart(form, payload.fields || {});
    window.pcOffcanvas?.open("updateChart");
  });

  /** Narrowest a column gets, and the gap between columns and cards (app.css .pc-viz-column). */
  const COLUMN_MIN = 420;
  const GAP = 16;

  /** Most a card grows to line a column's bottom up with the tallest. */
  const STRETCH_MAX = 96;

  /** Most points a line chart takes in one column; with more it's drawn across two. */
  const WIDE_POINTS = 10;

  /** Deals the shown cards into columns, shortest first, then evens out their bottoms. */
  function pack() {
    if (!board || !cards.size) return;
    // never more columns than charts, so one or two charts take the width there is
    const count = Math.max(1, Math.min(3, cards.size, Math.floor((board.clientWidth + GAP) / (COLUMN_MIN + GAP))));

    board.querySelectorAll(".pc-viz-columns").forEach((segment) => segment.remove());
    board.classList.add("pc-viz-packed");

    // runs of cards dealt into columns; a wide chart (with room for one) a band of its own between them
    let run = [];
    const flush = () => {
      if (!run.length) return;
      const holder = document.createElement("div");
      holder.className = "pc-viz-columns";
      const columns = Array.from({ length: Math.min(count, run.length) }, () => {
        const column = document.createElement("div");
        column.className = "pc-viz-column";
        holder.append(column);
        return column;
      });
      board.append(holder);
      // what the column's cards fill (the column itself is stretched to the row's height)
      const filled = (column) => [...column.children].reduce((sum, card) => sum + card.offsetHeight + GAP, 0);
      run.forEach((card) => columns.reduce((low, column) => (filled(column) < filled(low) ? column : low)).append(card));
      run = [];
    };
    const entries = [...cards.entries()];
    for (let index = 0; index < entries.length; index++) {
      const [name, card] = entries[index];
      card.style.minHeight = "";
      if (count < 2 || !isWide(name)) {
        run.push(card);
        continue;
      }
      flush();

      // the wide chart beside one column (2:1 with three columns, 1:1 with two), the charts after it stacked in that
      // column until they reach its height; the row is then evened like any run of columns
      const row = document.createElement("div");
      row.className = "pc-viz-columns pc-viz-wide-row";
      const band = document.createElement("div");
      band.className = count === 3 ? "pc-viz-column pc-viz-wide-column" : "pc-viz-column";
      band.dataset.vizWide = "";
      band.append(card);
      const side = document.createElement("div");
      side.className = "pc-viz-column";
      row.append(band, side);
      board.append(row);
      // measured by their cards: the columns themselves stretch to the row's height
      const stacked = () => [...side.children].reduce((sum, item) => sum + item.offsetHeight + GAP, 0);
      while (index + 1 < entries.length && !isWide(entries[index + 1][0]) && stacked() < card.offsetHeight) {
        const [, next] = entries[++index];
        next.style.minHeight = "";
        side.append(next);
      }
      if (!side.children.length) side.remove();
    }
    flush();
    even();
  }

  /** Whether a chart is drawn wide (two columns): a line across more than WIDE_POINTS groups or steps. */
  function isWide(name) {
    const summary = analytics.summary(name);
    const points = (summary.groups || summary.categories || []).length;
    return analytics.visual(name) === "line" && points > WIDE_POINTS;
  }

  /** Puts the cards back in the tab's order, unstretched (for print, which is one column). */
  function unpack() {
    if (!board?.classList.contains("pc-viz-packed")) return;
    cards.forEach((card) => {
      card.style.minHeight = "";
      board.append(card);
    });
    board.querySelectorAll(".pc-viz-columns").forEach((segment) => segment.remove());
    board.classList.remove("pc-viz-packed");
  }
  /**
   * A chart's width on paper: half an A4 page's content (186mm ≈ 703px, app.css @page, less the 16px between the two
   * cards of a row) less the card's padding and border. Set, never measured: a chart measured at print time takes the
   * screen's size, as the legacy reports' did.
   */
  const PRINT_CHART_WIDTH = Math.floor((703 - GAP) / 2) - 2 * 20 - 2;

  let printing = false;

  /** Rows of two cards in the tab's order, every shown chart redrawn at the print width, legends beneath. */
  function printLayout() {
    if (printing) return;
    printing = true;
    unpack();

    const shown = [...cards.values()];
    for (let index = 0; index < shown.length; index += 2) {
      const row = document.createElement("div");
      row.className = "pc-viz-print-row";
      row.append(...shown.slice(index, index + 2));
      board.append(row);
    }

    shown.forEach((card) => {
      const options = analytics.chart(card.dataset.vizCard, null, { width: PRINT_CHART_WIDTH });
      const holder = card.querySelector('[data-viz-pane="chart"] [data-pc-chart]');
      if (!options || !holder) return;
      const printed = { ...options, chart: { ...options.chart, width: PRINT_CHART_WIDTH } };
      // a legend beside the chart goes under it in the narrower print column
      if (options.legend?.position === "right") printed.legend = { ...options.legend, position: "bottom" };
      window.pcCharts?.render(holder, printed);
    });
  }

  /** Back to the columns, every chart redrawn at its column's width. */
  function screenLayout() {
    if (!printing) return;
    printing = false;
    board.querySelectorAll(".pc-viz-print-row").forEach((row) => {
      row.replaceWith(...row.children);
    });
    pack();
    cards.forEach((_card, name) => paint(name));
    even();
  }

  window.addEventListener("beforeprint", printLayout);
  window.addEventListener("afterprint", screenLayout);

  /** Stretches the shorter columns' cards toward the tallest column, at most STRETCH_MAX each. */
  function even() {
    board?.querySelectorAll(".pc-viz-columns").forEach(evenColumns);
  }

  /** Evens one run of columns. */
  function evenColumns(holder) {
    const columns = [...holder.querySelectorAll(".pc-viz-column")];
    const shown = columns.map((column) => [...column.children]);
    shown.flat().forEach((card) => (card.style.minHeight = ""));
    columns.forEach((column) => column.classList.remove("pc-viz-column-even"));
    if (columns.length < 2) return;

    const heights = shown.map((list) => list.reduce((sum, card) => sum + card.offsetHeight, 0) + GAP * Math.max(0, list.length - 1));
    const tallest = Math.max(...heights);
    shown.forEach((list, index) => {
      const shortfall = tallest - heights[index];
      if (!list.length || shortfall < 1) return;
      const each = shortfall / list.length;
      // within reach: the column fills exactly (its cards share the room); beyond, each grows by the most allowed
      if (each <= STRETCH_MAX) columns[index].classList.add("pc-viz-column-even");
      else list.forEach((card) => (card.style.minHeight = `${card.offsetHeight + STRETCH_MAX}px`));
    });
  }

  /** Brings a card in line with the engine: its visual, its chart drawn when it has one. */
  function paint(name) {
    const card = cards.get(name);
    if (!card) return;

    const visual = analytics.visual(name);
    // the menu ticks the visual showing; its button wears that visual's icon
    card.querySelectorAll("[data-viz-visual]").forEach((item) => {
      const showing = item.dataset.vizVisual === visual;
      item.classList.toggle("pc-dropdown-item-active", showing);
      item.setAttribute("aria-checked", showing ? "true" : "false");
      const icon = card.querySelector("[data-viz-visual-icon]");
      if (showing && icon) icon.className = `fa-regular fa-${item.dataset.vizIcon} text-base`;
    });

    const chartPane = card.querySelector('[data-viz-pane="chart"]');
    if (!chartPane) return;
    const tablePane = card.querySelector('[data-viz-pane="table"]');
    const options = analytics.chart(name);
    chartPane.hidden = !options;
    tablePane?.classList.toggle("sr-only", !!options);
    // disabled rather than hidden, so the buttons beside it don't shift
    const png = card.querySelector("[data-viz-png]");
    if (png) png.disabled = !options;

    if (options) {
      const holder = chartPane.querySelector("[data-pc-chart]");
      holder.setAttribute("aria-label", analytics.describe(name));
      const drawn = analytics.chart(name, null, { width: chartPane.clientWidth });
      // the space held for the chart follows the visual's own height, not the one the card opened with
      const canvas = holder.querySelector("[data-pc-chart-canvas]");
      if (canvas) canvas.style.minHeight = `${drawn.chart.height}px`;
      window.pcCharts?.render(holder, drawn);
    }
  }

  // a new visual changes the card's height, and maybe whether it's drawn wide: re-dealt when that changes
  analytics.on("change", ({ name }) => {
    const card = cards.get(name);
    if (card && isWide(name) !== !!card.closest("[data-viz-wide]")) pack();
    paint(name);
    even();
  });

  document.addEventListener("click", (event) => {
    const card = event.target.closest("[data-viz-card]");
    const visualButton = event.target.closest("[data-viz-visual]");
    if (card && visualButton) {
      analytics.setVisual(card.dataset.vizCard, visualButton.dataset.vizVisual);
      return;
    }
    if (card && event.target.closest("[data-viz-png]")) {
      download(card);
      return;
    }
    const step = event.target.closest("[data-viz-step]");
    if (step) {
      turnPage(step.closest("[data-viz-answers]"), Number(step.dataset.vizStep));
      return;
    }
    if (event.target.closest("[data-viz-print]")) {
      // laid out and drawn for paper first, then printed once the charts have painted
      printLayout();
      requestAnimationFrame(() => requestAnimationFrame(() => window.print()));
    }
  });

  /** The card's chart as a PNG, named after the question. */
  function download(card) {
    const chart = window.pcCharts?.get(card.querySelector("[data-pc-chart]"));
    if (!chart) return;
    const title = analytics.summary(card.dataset.vizCard).title;
    chart.dataURI({ scale: 2 }).then(({ imgURI }) => {
      const link = document.createElement("a");
      link.href = imgURI;
      link.download = `${title.replace(/[\\/:*?"<>|]+/g, " ").trim().slice(0, 80) || "chart"}.png`;
      link.click();
    });
  }

  /** One answer row, as partials/answers renders it. */
  function answerRow(item) {
    const row = document.createElement("li");
    row.className = "pc-viz-answer";
    const text = document.createElement("p");
    text.className = "pc-viz-answer-text";
    text.textContent = item.text;
    const meta = document.createElement("span");
    meta.className = "pc-viz-answer-meta";
    const link = document.createElement("a");
    link.href = item.url;
    link.textContent = item.reference;
    meta.append(link, ` · ${item.date}`);
    row.append(text, meta);
    return row;
  }

  /** Loads the newer (-1) or older (1) page of a list of answers. */
  async function turnPage(box, step) {
    const page = Number(box.dataset.vizPage) + step;
    if (page < 1 || page > Number(box.dataset.vizLast)) return;

    const url = new URL(config.answersUrl, location.href);
    url.searchParams.set("question", box.dataset.vizAnswers);
    url.searchParams.set("page", String(page));
    if (box.dataset.vizOther === "1") url.searchParams.set("other", "1");

    box.setAttribute("aria-busy", "true");
    try {
      const response = await fetch(url, { headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" } });
      if (!response.ok) throw new Error(response.statusText);
      const { answers } = await response.json();
      box.querySelector("[data-viz-answers-list]").replaceChildren(...answers.items.map(answerRow));
      box.dataset.vizPage = answers.page;
      box.dataset.vizLast = answers.lastPage;
      const from = (answers.page - 1) * answers.perPage + 1;
      box.querySelector("[data-viz-range]").textContent = pc.t(config.strings.showing, {
        from: from.toLocaleString(root.lang),
        to: (from + answers.items.length - 1).toLocaleString(root.lang),
        total: answers.total.toLocaleString(root.lang),
      });
      box.querySelector('[data-viz-step="-1"]').disabled = answers.page <= 1;
      box.querySelector('[data-viz-step="1"]').disabled = answers.page >= answers.lastPage;
    } catch {
      window.toast?.error({ message: config.strings.failed });
    } finally {
      box.removeAttribute("aria-busy");
    }
  }

  // dealt first, so each chart is drawn at its column's width, then evened (again once the fonts have settled)
  const layout = () => {
    pack();
    cards.forEach((_card, name) => paint(name));
    even();
  };
  const start = () => {
    layout();
    document.fonts?.ready.then(even);
  };

  // a resize that changes how many columns fit re-deals and redraws them; otherwise only the heights move
  let resizeTimer;
  let lastWidth = board?.clientWidth;
  window.addEventListener("resize", () => {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(() => {
      if (!board || board.clientWidth === lastWidth) return;
      const fits = (width) => Math.max(1, Math.min(3, cards.size, Math.floor((width + GAP) / (COLUMN_MIN + GAP))));
      fits(board.clientWidth) === fits(lastWidth) ? even() : layout();
      lastWidth = board.clientWidth;
    }, 150);
  });
  if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", start);
  else start();
}

visualize();
