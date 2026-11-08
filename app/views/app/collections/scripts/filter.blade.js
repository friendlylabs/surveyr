/**
 * Submissions filter view
 * --------------------------------------------------------------------------
 * The submissions table's filter UI: a full-screen offcanvas with a date
 * range / flag strip and Bootstrap rule rows. Applying reloads the page with
 * ?filters=<base64>, which the server and the visualize link pick up.
 *
 * Logic (vocabulary, compiling, URL encoding) is in shared/scripts/filter-core.
 *
 * Controls are found by data-role inside the root, falling back to the
 * page's ids when the root is the document.
 *
 * Roles:   fixed-filters, rules, add-rule, apply (optional)
 * Ids:     #fixedFilterContainer, #filterRuleContainer, #addRuleBtn, #applyFiltersBtn
 */
window.CollectionFilterView = (function () {
  'use strict';

  const PAGE_IDS = {
    'fixed-filters': 'fixedFilterContainer',
    'rules':         'filterRuleContainer',
    'add-rule':      'addRuleBtn',
    'apply':         'applyFiltersBtn',
  };

  function initWidgets(scope) {
    if (window.CustomSelect) new CustomSelect();

    if (window.flatpickr) {
      scope.querySelectorAll('input[type="date"]:not(.flatpickr-input)').forEach(input => {
        input.placeholder = "Select date";
        flatpickr(input, {
          dateFormat: "Y-m-d",
          allowInput: true,
          altInput: true,
          altFormat: "F j, Y",
        });
      });
    }
  }

  function create(root) {
    root = root || document;
    const isDocument = root === document;

    let ctx = null;
    let ruleContainer = null;
    // the range input keeps the saved from/to itself, so a lone to_date survives a round trip
    const range = { from: '', to: '', fp: null };
    let addBtn = null;
    let applyBtn = null;

    function el(role) {
      const scoped = root.querySelector('[data-role="' + role + '"]');
      if (scoped) return scoped;
      return isDocument && PAGE_IDS[role] ? document.getElementById(PAGE_IDS[role]) : null;
    }

    // generated controls keep their ids only on the document-scoped instance
    const idAttr = id => isDocument ? ' id="' + id + '"' : '';

    function renderFixedFilters() {
      const container = el('fixed-filters');
      if (!container) return;

      // one compact scope row, like the report filters: date range + flag, no labels
      container.innerHTML = `
        <div class="fixed-filters-section mb-4 p-3">
          <div class="cf-scope">
            <div class="cf-range">
              <input type="text"${idAttr('dateRange')} data-role="date-range" class="form-control" placeholder="Any submission date" aria-label="Submission date range" />
              <button type="button" class="cf-range-clear" data-role="range-clear" title="Clear dates" aria-label="Clear dates" hidden><i class="fa-solid fa-xmark"></i></button>
            </div>
            ${ctx.flags.length ? `
            <div class="cf-flag">
              <select${idAttr('flagFilter')} data-role="flag" class="form-select" multiple data-custom-select="true" data-select-search="true" data-select-placeholder="Any flag" aria-label="Flag">
                ${ctx.flags.map(f => `<option value="${ctx.esc(f.value)}">${ctx.esc(f.text)}</option>`).join('')}
              </select>
            </div>` : ''}
          </div>
        </div>
      `;
    }

    /* submission date range: one flatpickr in range mode, like the report filters */
    function mountRange() {
      const input = el('date-range');
      if (!input) return;
      el('range-clear').addEventListener('click', () => { writeRange('', ''); });
      if (!window.flatpickr) { input.type = 'hidden'; return; }

      range.fp = flatpickr(input, {
        mode: 'range',
        dateFormat: 'Y-m-d',
        altInput: true,
        altFormat: 'M j, Y',
        static: true,          // calendar stays inside the offcanvas and its focus trap
        onChange(dates, _str, fp) {
          range.from = dates[0] ? fp.formatDate(dates[0], 'Y-m-d') : '';
          range.to = dates[1] ? fp.formatDate(dates[1], 'Y-m-d') : '';
          syncRangeClear();
        },
      });
    }

    function writeRange(from, to) {
      range.from = from || '';
      range.to = to || '';
      if (range.fp) range.fp.setDate([range.from, range.to].filter(Boolean), false);
      syncRangeClear();
    }

    function syncRangeClear() {
      const clear = el('range-clear');
      if (clear) clear.hidden = !(range.from || range.to);
    }

    function createRuleRow(preset = {}) {
      const esc = ctx.esc;
      const row = document.createElement("div");
      row.className = "ruleCard p-3 py-5 mb-2 position-relative";

      const questionOptions = ctx.questions.map(q => `<option value="${esc(q.name)}"${preset.question === q.name ? " selected" : ""}>${esc(q.title)}</option>`).join("");

      row.innerHTML = `
        <button type="button" class="btn position-absolute end-0 top-0 m-2" aria-label="Remove rule" data-role="remove-rule">
          <i class="fa fa-trash-alt text-danger"></i>
        </button>
        <div class="row g-2">
          <div class="col-md-3">
            <select class="form-select question-select">
              <option value="">Select question...</option>
              ${questionOptions}
            </select>
          </div>
          <div class="col-md-2">
            <select class="form-select filter-type">
              <option value="">Type...</option>
              ${ctx.types.map(type => `<option value="${type}"${preset.type === type ? " selected" : ""}>${ctx.typeLabel(type)}</option>`).join("")}
            </select>
          </div>
          <div class="col-md-2">
            <select class="form-select operator-select">
              <option value="">Operator...</option>
            </select>
          </div>
          <div class="col-md-4 value-field"></div>
        </div>
      `;

      const questionSelect = row.querySelector(".question-select");
      const typeSelect = row.querySelector(".filter-type");
      const operatorSelect = row.querySelector(".operator-select");
      const valueField = row.querySelector(".value-field");

      function populateOperators() {
        operatorSelect.innerHTML = `<option value="">Operator...</option>`
          + ctx.operatorsFor(typeSelect.value).map(op =>
              `<option value="${op}"${preset.operator === op ? " selected" : ""}>${ctx.operatorLabel(op)}</option>`
            ).join("");
      }

      function populateValueField() {
        valueField.innerHTML = "";

        switch (ctx.valueKind(typeSelect.value, operatorSelect.value)) {
          case 'choices': {
            const wanted = Array.isArray(preset.value) ? preset.value.map(String) : [];
            const select = document.createElement("select");
            select.className = "form-select value-input";
            select.multiple = true;
            select.setAttribute("data-custom-select", "true");
            select.setAttribute("data-select-search", "true");
            select.setAttribute("data-select-placeholder", "Select options...");
            select.innerHTML = ctx.choicesFor(questionSelect.value).map(c =>
              `<option value="${esc(c.value)}"${wanted.includes(c.value) ? " selected" : ""}>${esc(c.text)}</option>`
            ).join("");
            valueField.appendChild(select);
            if (window.CustomSelect) new CustomSelect();
            break;
          }
          case 'range':
            valueField.innerHTML = `
              <div class="d-flex gap-2">
                <input type="number" class="form-control value-input-min" placeholder="Min" value="${esc(preset.value?.min || '')}">
                <input type="number" class="form-control value-input-max" placeholder="Max" value="${esc(preset.value?.max || '')}">
              </div>`;
            break;
          case 'number':
          case 'text':
            valueField.innerHTML = `<input type="text" class="form-control value-input" value="${esc(typeof preset.value === 'object' ? '' : (preset.value || ''))}">`;
            break;
          case 'list':
            valueField.innerHTML = `<input type="text" class="form-control value-input" value="${esc((Array.isArray(preset.value) ? preset.value : []).join(', '))}" placeholder="Comma-separated">`;
            break;
        }
      }

      operatorSelect.addEventListener("change", populateValueField);
      typeSelect.addEventListener("change", () => { populateOperators(); populateValueField(); });
      questionSelect.addEventListener("change", () => { if (typeSelect.value === "choice") populateValueField(); });

      if (preset.operator) populateOperators();
      if (preset.value !== undefined) populateValueField();
      setTimeout(() => {
        if (window.CustomSelect && row.querySelector("[data-custom-select]")) new CustomSelect();
      }, 100);

      return row;
    }

    function readRule(card) {
      const type = card.querySelector(".filter-type").value;
      const operator = card.querySelector(".operator-select").value;
      let value;

      switch (ctx.valueKind(type, operator)) {
        case 'range':
          value = { min: card.querySelector(".value-input-min")?.value, max: card.querySelector(".value-input-max")?.value };
          break;
        case 'choices':
          value = Array.from(card.querySelector(".value-input")?.selectedOptions || []).map(o => o.value);
          break;
        case 'none':
          value = null;
          break;
        default:
          value = card.querySelector(".value-input")?.value;
      }

      return { question: card.querySelector(".question-select").value, type, operator, value };
    }

    const onAdd = () => ruleContainer.appendChild(createRuleRow());
    const onApply = () => ctx.apply();
    const onRemove = e => {
      const btn = e.target.closest('[data-role="remove-rule"]');
      if (btn) btn.closest('.ruleCard').remove();
    };

    return {
      mount(context) {
        ctx = context;
        ruleContainer = el('rules');
        if (!ruleContainer) throw new Error('CollectionFilterView: no rules container found');

        addBtn = el('add-rule');
        applyBtn = el('apply');
        if (addBtn) addBtn.addEventListener("click", onAdd);
        if (applyBtn) applyBtn.addEventListener("click", onApply);
        ruleContainer.addEventListener("click", onRemove);

        renderFixedFilters();
        mountRange();
        initWidgets(root);
      },

      read() {
        const flagSelect = el('flag');
        return {
          from_date: range.from,
          to_date: range.to,
          flag: flagSelect ? Array.from(flagSelect.selectedOptions).map(o => o.value) : [],
          rules: Array.from(ruleContainer.querySelectorAll(".ruleCard")).map(readRule),
        };
      },

      write(state) {
        writeRange(state.from_date, state.to_date);

        const flag = el('flag');
        if (flag) Array.from(flag.options).forEach(o => { o.selected = state.flag.includes(o.value); });

        ruleContainer.innerHTML = '';
        state.rules.forEach(rule => ruleContainer.appendChild(createRuleRow(rule)));

        initWidgets(root);
        // CustomSelect draws a select once; redraw so the values just written show
        if (window.CustomSelect) new CustomSelect().refresh();
      },

      destroy() {
        if (addBtn) addBtn.removeEventListener("click", onAdd);
        if (applyBtn) applyBtn.removeEventListener("click", onApply);
        if (ruleContainer) ruleContainer.removeEventListener("click", onRemove);
        if (range.fp) range.fp.destroy();
      },
    };
  }

  return { create };
})();

// page bootstrap: the submissions page declares `const survey`, which is a global
// binding but not a window property
if (document.getElementById('filterRuleContainer') && (typeof survey !== 'undefined' || window.survey)) {
  window.filterBuilder = FilterBuilder.create({
    survey: typeof survey !== 'undefined' ? survey : window.survey,
    flags: @json($flags ?? []),
    view: CollectionFilterView.create(document),
    onApply: FilterBuilder.navigateWithFilters,
  });
  // ad-hoc ?filters= wins; otherwise start from the saved filter the page was opened with
  const savedFilter = @json(isset($activeFilter) && $activeFilter ? $activeFilter->only(['id', 'name', 'description', 'filters']) : null);
  if (new URLSearchParams(window.location.search).has('filters')) window.filterBuilder.loadFiltersFromURL();
  else if (savedFilter && savedFilter.filters && !Array.isArray(savedFilter.filters)) window.filterBuilder.load(savedFilter.filters);

  // "Save as new filter" / "Update <name>": store the panel's rules as a saved filter
  document.querySelectorAll('[data-save-filter]').forEach(button => {
    button.addEventListener('click', () => {
      const isUpdate = button.dataset.saveFilter === 'update';
      const name = isUpdate ? savedFilter.name : document.getElementById('saveFilterName').value.trim();
      if (!name) return toast.error({ message: 'Give the filter a name first' });

      const url = isUpdate
        ? `@route('collections.filters.update', ':id')`.replace(':id', savedFilter.id)
        : `@route('collections.filters.store', isset($form) ? $form->id : 0)`;

      $.ajax({
        url,
        method: 'POST',
        data: {
          _token: `{{ csrf_token() }}`,
          name,
          description: isUpdate ? (savedFilter.description || '') : '',
          filters: JSON.stringify(window.filterBuilder.compileFilters()),
          return: 'submissions',
        },
      }).then(response => {
        if (!response.status) return toast.error({ message: response.message || 'Could not save the filter' });
        toast.success({ message: response.message });
        if (response.redirect) setTimeout(() => { window.location.href = response.redirect; }, 600);
      }).catch(() => toast.error({ message: 'Could not save the filter' }));
    });
  });
}
