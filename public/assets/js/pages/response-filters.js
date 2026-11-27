/**
 * The responses filter panel (app/responses/partials/filters): rule rows built from #responseFiltersConfig (the
 * questions a rule can use, the operators for each kind, the filters in force). A row is a question, an operator and
 * a value that fits them: text, a number, a min / max pair, or a choice of the question's options; "is empty" and
 * "is answered" need none. Apply reloads the table with ?filters= (base64url JSON, App\Support\SubmissionFilters
 * reads it); Save as view / Save to view post the same filters as JSON. Rules are added from the add line, which shows
 * on hover (app.css .pc-filter-insert).
 */
(function () {
  const panel = document.getElementById("responsesFilter");
  const configEl = document.getElementById("responseFiltersConfig");
  if (!panel || !configEl) return;

  const config = JSON.parse(configEl.textContent);
  const fields = config.fields || {};
  const names = Object.keys(fields);
  const list = panel.querySelector("[data-filter-rules]");
  const addButton = panel.querySelector("[data-filter-add]");
  const text = config.strings;

  /** An element with a class and attributes. */
  function el(tag, className, attrs = {}) {
    const node = document.createElement(tag);
    if (className) node.className = className;
    Object.entries(attrs).forEach(([key, value]) => node.setAttribute(key, value));
    return node;
  }

  function option(value, label, selected) {
    const opt = el("option", "", { value });
    opt.textContent = label;
    opt.selected = !!selected;
    return opt;
  }

  /** The value control for a question's operator, filled with `value` when it fits. */
  function valueControl(name, operator, value) {
    const field = fields[name];
    const box = el("div", "pc-filter-value");
    if (!field || operator === "empty" || operator === "notEmpty") return box;

    if (operator === "in" || operator === "notIn") {
      const chosen = (Array.isArray(value) ? value : []).map(String);
      const select = el("select", "pc-select", { multiple: "", "data-placeholder": text.choose, "aria-label": text.value, "data-filter-input": "choices" });
      Object.entries(field.choices).forEach(([key, label]) => select.append(option(key, label, chosen.includes(String(key)))));
      box.append(select);
    } else if (operator === "between") {
      const range = value && typeof value === "object" ? value : {};
      ["min", "max"].forEach((end) => {
        const input = el("input", "pc-input", { type: "number", step: "any", placeholder: text[end], "aria-label": text[end], "data-filter-input": end });
        if (range[end] !== null && range[end] !== undefined) input.value = range[end];
        box.append(input);
      });
    } else {
      const input = el("input", "pc-input", { type: field.kind === "number" ? "number" : "text", step: "any", placeholder: text.value, "aria-label": text.value, "data-filter-input": "text" });
      if (typeof value === "string" || typeof value === "number") input.value = value;
      box.append(input);
    }
    return box;
  }

  /** The operator select for a question's kind. */
  function operatorSelect(name, current) {
    const ops = config.operators[fields[name]?.kind] || [];
    const select = el("select", "pc-select", { "aria-label": text.operator || "Operator", "data-filter-operator": "" });
    ops.forEach((op) => select.append(option(op, config.labels[op] || op, op === current)));
    return select;
  }

  /** One rule row. */
  function row(rule = {}) {
    const name = names.includes(rule.question) ? rule.question : names[0];
    const node = el("div", "pc-filter-rule", { "data-filter-rule": "" });

    const question = el("select", "pc-select", { "aria-label": text.question, "data-filter-question": "" });
    names.forEach((key) => question.append(option(key, fields[key].title, key === name)));

    const remove = el("button", "pc-btn pc-btn-ghost pc-btn-icon pc-btn-sm", { type: "button", "aria-label": text.remove, "data-filter-remove": "" });
    remove.innerHTML = '<i class="fa-regular fa-xmark" aria-hidden="true"></i>';

    const ops = config.operators[fields[name].kind];
    const operator = operatorSelect(name, ops.includes(rule.operator) ? rule.operator : ops[0]);
    node.append(question, operator, valueControl(name, operator.value, rule.value), remove);
    return node;
  }

  /** No add line when the form has nothing to filter on. */
  function syncEmpty() {
    addButton.hidden = !names.length;
  }

  /** The panel's filters, in the shape SubmissionFilters takes. */
  function read() {
    const rules = [...list.querySelectorAll("[data-filter-rule]")].map((node) => {
      const question = node.querySelector("[data-filter-question]").value;
      const operator = node.querySelector("[data-filter-operator]").value;
      const choices = node.querySelector('[data-filter-input="choices"]');
      const single = node.querySelector('[data-filter-input="text"]');
      const min = node.querySelector('[data-filter-input="min"]');
      let value = null;
      if (choices) value = [...choices.selectedOptions].map((opt) => opt.value);
      else if (min) value = { min: min.value === "" ? null : min.value, max: node.querySelector('[data-filter-input="max"]').value || null };
      else if (single) value = single.value;
      return { question, operator, value };
    });
    return {
      from: panel.querySelector('input[name="from"]')?.value || null,
      to: panel.querySelector('input[name="to"]')?.value || null,
      rules,
    };
  }

  /** UTF-8 JSON as base64url (what FormResponsesController::requestedFilters decodes). */
  function encode(filters) {
    const bytes = new TextEncoder().encode(JSON.stringify(filters));
    return btoa(String.fromCharCode(...bytes)).replace(/\+/g, "-").replace(/\//g, "_");
  }

  /** Reloads the table with the given filters (from the first page, no response open). */
  function apply(filters) {
    const url = new URL(location.href);
    const empty = !filters.from && !filters.to && !filters.rules.length;
    // with a view open, an empty set still has to be sent, or the view's own filters would come back
    if (empty && !url.searchParams.has("view")) url.searchParams.delete("filters");
    else url.searchParams.set("filters", encode(filters));
    ["page", "response"].forEach((key) => url.searchParams.delete(key));
    window.pcPageLoader?.show();
    location.href = url.toString();
  }

  list.addEventListener("change", (e) => {
    const node = e.target.closest("[data-filter-rule]");
    if (!node) return;
    if (e.target.matches("[data-filter-question]")) {
      // a new question: its kind's operators, and a fresh value
      const name = e.target.value;
      const operator = operatorSelect(name, config.operators[fields[name].kind][0]);
      node.querySelector("[data-filter-operator]").replaceWith(operator);
      node.querySelector(".pc-filter-value").replaceWith(valueControl(name, operator.value, null));
    } else if (e.target.matches("[data-filter-operator]")) {
      const name = node.querySelector("[data-filter-question]").value;
      const before = read().rules[[...list.children].indexOf(node)]?.value;
      node.querySelector(".pc-filter-value").replaceWith(valueControl(name, e.target.value, before));
    }
  });

  list.addEventListener("click", (e) => {
    const remove = e.target.closest("[data-filter-remove]");
    if (!remove) return;
    remove.closest("[data-filter-rule]").remove();
    syncEmpty();
  });

  addButton.addEventListener("click", () => {
    const node = row();
    list.append(node);
    syncEmpty();
    node.querySelector("[data-filter-question]").focus();
  });

  panel.querySelector("[data-filter-apply]").addEventListener("click", () => apply(read()));
  panel.querySelector("[data-filter-clear]").addEventListener("click", () => apply({ from: null, to: null, rules: [] }));

  // capture: the save forms get the panel's filters before submitForm() reads them
  document.addEventListener("submit", (e) => {
    const form = e.target;
    if (!form.closest("#saveView, [data-filter-save-into]")) return;
    const input = form.querySelector('input[name="filters"]');
    if (input) input.value = JSON.stringify(read());
  }, true);

  (config.filters?.rules || []).forEach((rule) => list.append(row(rule)));
  syncEmpty();

  // ?filter=1 (New view, Edit filters) opens the panel on arrival
  const url = new URL(location.href);
  if (url.searchParams.has("filter")) {
    url.searchParams.delete("filter");
    history.replaceState(history.state, "", url);
    // after DOMContentLoaded, when pc-offcanvas.js (deferred like this file) has loaded
    document.addEventListener("DOMContentLoaded", () => {
      window.pcOffcanvas?.open("responsesFilter");
      if (!list.children.length) addButton.click();
    });
  }
})();
