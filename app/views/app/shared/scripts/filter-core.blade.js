/**
 * Submission filter core
 * --------------------------------------------------------------------------
 * The logic half of the submission filters, shared by every page that filters
 * submissions. It owns the rule vocabulary and compiles a view's state into
 * the object Collection::formCollections understands:
 *
 *   { from_date, to_date, flag: [], "<question>": [{ type, operator, value }] }
 *
 * Rules on different questions must all match; rules on the same question
 * match if any of them does.
 *
 * The UI lives in a view, one per page:
 *   collections/scripts/filter.blade.js  -> CollectionFilterView (submissions table)
 *   reports/scripts/filters.blade.js     -> ReportFilterView     (report editor)
 *
 * A view is { mount(ctx), read() -> state, write(state), destroy() } where
 *
 *   state = { from_date, to_date, flag: [], rules: [{ question, type, operator, value }] }
 *
 * and ctx (see context()) hands it the questions, flags, vocabulary and
 * the apply()/changed() callbacks. The view never compiles; the core never
 * touches the DOM.
 *
 * FilterBuilder.create({ survey, flags, view, onApply, onChange })
 *   -> { compileFilters, load, loadFiltersFromURL, destroy }
 */
window.FilterBuilder = (function () {
  'use strict';

  const TYPES = {
    text:    ["contains", "startsWith", "endsWith"],
    fuzzy:   ["contains"],
    numeric: ["equals", "notEquals", "gt", "lt", "gte", "lte", "range"],
    check:   ["empty", "notEmpty"],
    choice:  ["equals", "notEquals", "contains"],
  };

  const TYPE_LABELS = {
    text: "Text",
    fuzzy: "Fuzzy",
    numeric: "Numeric",
    check: "Check",
    choice: "Choice",
  };

  const OPERATOR_LABELS = {
    contains: "Contains",
    startsWith: "Starts With",
    endsWith: "Ends With",
    equals: "Equals",
    notEquals: "Not Equals",
    gt: "Greater Than",
    lt: "Less Than",
    gte: "Greater or Equal",
    lte: "Less or Equal",
    range: "Between",
    empty: "Is Empty",
    notEmpty: "Is Not Empty",
  };

  const FIXED_KEYS = ['from_date', 'to_date', 'flag'];
  const CHOICE_TYPES = ['checkbox', 'radiogroup', 'dropdown', 'tagbox', 'imagepicker', 'ranking'];

  function esc(s) {
    return String(s == null ? '' : s)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
  }

  function operatorsFor(type) {
    return TYPES[type] || [];
  }

  /** What value control a rule needs: none | choices | range | number | list | text. */
  function valueKind(type, operator) {
    if (type === 'check') return 'none';
    if (type === 'choice') return 'choices';
    if (type === 'fuzzy') return 'list';
    if (type === 'numeric') return operator === 'range' ? 'range' : 'number';
    return 'text';
  }

  function normalizeValue(type, operator, value) {
    switch (valueKind(type, operator)) {
      case 'none':    return null;
      case 'choices': return Array.isArray(value) ? value.map(String) : [];
      case 'range':   return { min: (value && value.min) || '', max: (value && value.max) || '' };
      case 'list':    return Array.isArray(value) ? value : String(value || '').split(',').map(x => x.trim()).filter(Boolean);
      default:        return value == null ? '' : value;
    }
  }

  /** state -> the filter object the server understands; incomplete rules are dropped. */
  function compile(state) {
    const s = state || {};
    const out = {};

    if (s.from_date) out.from_date = s.from_date;
    if (s.to_date) out.to_date = s.to_date;
    if (Array.isArray(s.flag) && s.flag.length) out.flag = s.flag.slice();

    (s.rules || []).forEach(r => {
      if (!r || !r.question || !r.type || !r.operator) return;
      (out[r.question] = out[r.question] || []).push({
        type: r.type,
        operator: r.operator,
        value: normalizeValue(r.type, r.operator, r.value),
      });
    });

    return out;
  }

  /** A saved filter object -> state. */
  function parse(filters) {
    const f = (filters && typeof filters === 'object') ? filters : {};
    const rules = [];

    Object.keys(f).forEach(question => {
      if (FIXED_KEYS.includes(question) || question === 'review_type' || !Array.isArray(f[question])) return;
      f[question].forEach(rule => rules.push({ question, ...rule }));
    });

    return {
      from_date: f.from_date || '',
      to_date: f.to_date || '',
      flag: Array.isArray(f.flag) ? f.flag.map(String) : [],
      rules,
    };
  }

  /** How many constraints a compiled filter object carries (a date range counts once). */
  function countActive(filters) {
    const f = filters || {};
    return Object.keys(f).reduce((n, key) => {
      if (FIXED_KEYS.includes(key)) return n;
      return n + (Array.isArray(f[key]) ? f[key].length : 0);
    }, (f.from_date || f.to_date ? 1 : 0) + (Array.isArray(f.flag) && f.flag.length ? 1 : 0));
  }

  function encode(filters) {
    return btoa(encodeURIComponent(JSON.stringify(filters)));
  }

  function decode(encoded) {
    return JSON.parse(decodeURIComponent(atob(encoded)));
  }

  /** Reload the current page with ?filters=<base64>. */
  function navigateWithFilters(filters) {
    const url = new URL(window.location.href);
    url.searchParams.set("filters", encode(filters));
    window.location.href = url.toString();
  }

  function questionsOf(survey) {
    return survey.getAllQuestions().map(q => ({ name: q.name, title: q.title || q.name, model: q }));
  }

  function choicesOf(question) {
    if (!question) return [];
    const list = (question.choices && question.choices.length) ? question.choices : (question.visibleChoices || []);
    return list.map(opt => typeof opt === 'string'
      ? { value: opt, text: opt }
      : { value: String(opt.value), text: opt.text || String(opt.value) });
  }

  /** A sensible rule type for a question, so a view can preselect it. */
  function suggestType(question) {
    if (!question) return '';
    const kind = question.getType ? question.getType() : '';
    if (CHOICE_TYPES.includes(kind) || choicesOf(question).length) return 'choice';
    if (kind === 'rating' || (kind === 'text' && question.inputType === 'number')) return 'numeric';
    return 'text';
  }

  /** A form's flags ({key, label, color}) -> filter options, plus "Unflagged"; none when the form has no flags. */
  function normalizeFlags(flags) {
    const list = (Array.isArray(flags) ? flags : []).map(f => (f && typeof f === 'object')
      ? { value: String(f.key ?? f.value), text: f.label || f.text || String(f.key ?? f.value), color: f.color || null }
      : { value: String(f), text: String(f), color: null });
    return list.length ? list.concat({ value: '__none', text: 'Unflagged', color: null }) : [];
  }

  /** What a view gets on mount. */
  function context(survey, flags, hooks) {
    const questions = questionsOf(survey);
    const byName = name => (questions.find(q => q.name === name) || {}).model;

    return {
      questions,
      flags: normalizeFlags(flags),
      types: Object.keys(TYPES),
      typeLabel: t => TYPE_LABELS[t] || t,
      operatorLabel: o => OPERATOR_LABELS[o] || o,
      operatorsFor,
      valueKind,
      choicesFor: name => choicesOf(byName(name)),
      suggestType: name => suggestType(byName(name)),
      esc,
      apply: hooks.apply,
      changed: hooks.changed,
    };
  }

  function create(options) {
    const view = options.view;
    if (!view) throw new Error('FilterBuilder: a view is required');

    const compileFilters = () => compile(view.read());

    view.mount(context(options.survey, options.flags, {
      apply: () => options.onApply && options.onApply(compileFilters()),
      changed: () => options.onChange && options.onChange(compileFilters()),
    }));

    /** Push a saved filter object into the view (replaces whatever is shown). */
    function load(filters) {
      view.write(parse(filters));
    }

    function loadFiltersFromURL() {
      const encoded = new URLSearchParams(window.location.search).get("filters");
      if (!encoded) return;
      try {
        load(decode(encoded));
      } catch (e) {
        console.error("Failed to load saved filters", e);
      }
    }

    return {
      compileFilters,
      load,
      loadFiltersFromURL,
      destroy() { if (view.destroy) view.destroy(); },
    };
  }

  return {
    create,
    compile,
    parse,
    countActive,
    encode,
    decode,
    navigateWithFilters,
    esc,
  };
})();
