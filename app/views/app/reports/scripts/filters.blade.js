/**
 * Report filter view
 * --------------------------------------------------------------------------
 * The report editor's filter UI: one compact panel per source form, sized for
 * the narrow filters offcanvas and drawn in the studio's rp- vocabulary.
 *
 *   scope row   submission date range (flatpickr) + flag select
 *               (the select is left out when the form has no flag group)
 *   filters     stacked cards: question, then type + operator, then value;
 *               choice values are chips (searchable when long), the type
 *               is preselected from the question; the add trigger is a
 *               divider that shows while the pointer is over the panel
 *
 * Logic (vocabulary, compiling) is in shared/scripts/filter-core. Every edit
 * calls ctx.changed() so the editor can keep its per-form counts current.
 *
 * ReportFilterView.create(root) -> view for FilterBuilder.create({ view })
 */
window.ReportFilterView = (function () {
    'use strict';

    /** Past this many choices the chips get a search box and scroll. */
    const LONG_LIST = 16;

    function create(root) {
        let ctx = null;
        let rulesEl = null;

        const $ = sel => root.querySelector(sel);

        function chip(value, text, on) {
            return '<button type="button" class="rf-chip' + (on ? ' is-on' : '') + '" data-rf-value="' + ctx.esc(value) + '"'
                + ' aria-pressed="' + (on ? 'true' : 'false') + '">' + ctx.esc(text) + '</button>';
        }

        function options(list, selected, placeholder) {
            return (placeholder ? '<option value="">' + placeholder + '</option>' : '')
                + list.map(o => '<option value="' + ctx.esc(o.value) + '"' + (o.value === selected ? ' selected' : '') + '>' + ctx.esc(o.text) + '</option>').join('');
        }

        function renderShell() {
            const flags = ctx.flags.length
                ? '<div class="dropdown rf-flag">'
                +   '<button type="button" class="rf-input rf-select-btn" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" aria-label="Flag">'
                +     '<span data-rf="flag-label"></span><i class="fa-solid fa-chevron-down"></i>'
                +   '</button>'
                +   '<div class="dropdown-menu dropdown-menu-end rf-menu" data-rf="flags">'
                +     ctx.flags.map(f => '<label class="rf-menu-item"><input type="checkbox" class="form-check-input" value="' + ctx.esc(f.value) + '"><span>' + ctx.esc(f.text) + '</span></label>').join('')
                +   '</div>'
                + '</div>'
                : '';

            root.innerHTML = ''
                + '<div class="rf-scope">'
                +   '<div class="rf-range">'
                +     '<input type="text" class="rf-input" data-rf="range" placeholder="Any submission date" aria-label="Submission date range">'
                +     '<button type="button" class="rf-range-clear" data-rf="range-clear" title="Clear dates" aria-label="Clear dates" hidden><i class="fa-solid fa-xmark"></i></button>'
                +   '</div>'
                +   flags
                + '</div>'
                + '<div class="rf-rules" data-rf="rules"></div>'
                + '<button type="button" class="rf-add" data-rf="add" title="Add filter" aria-label="Add filter"><i class="fa-solid fa-plus"></i></button>';

            rulesEl = $('[data-rf="rules"]');
            mountRange();
            renderFlagLabel();
        }

        /* ------------------------------------------------------------------ *
         |  Scope: date range + flag                                           |
         * ------------------------------------------------------------------ */

        // the range keeps the saved from/to itself, so a lone to_date survives a round trip
        const range = { from: '', to: '', fp: null };

        function mountRange() {
            const input = $('[data-rf="range"]');
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
                    ctx.changed();
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
            $('[data-rf="range-clear"]').hidden = !(range.from || range.to);
        }

        function selectedFlags() {
            const menu = $('[data-rf="flags"]');
            return menu ? Array.from(menu.querySelectorAll('input:checked')).map(i => i.value) : [];
        }

        function renderFlagLabel() {
            const label = $('[data-rf="flag-label"]');
            if (!label) return;
            const picked = selectedFlags();
            const names = ctx.flags.filter(f => picked.includes(f.value)).map(f => f.text);
            label.textContent = names.length ? names.join(', ') : 'Any flag';
            label.classList.toggle('is-placeholder', !names.length);
        }

        /* ------------------------------------------------------------------ *
         |  Condition cards                                                    |
         * ------------------------------------------------------------------ */

        function ruleCard(rule) {
            const card = document.createElement('div');
            card.className = 'rf-rule';
            card.innerHTML = ''
                + '<div class="rf-rule-head">'
                +   '<select class="rf-input" data-rf="question">'
                +     options(ctx.questions.map(q => ({ value: q.name, text: q.title })), rule.question, 'Choose a question…')
                +   '</select>'
                +   '<button type="button" class="rf-icon-btn" data-rf="remove" title="Remove condition" aria-label="Remove condition"><i class="fa-solid fa-xmark"></i></button>'
                + '</div>'
                + '<div class="rf-rule-row">'
                +   '<select class="rf-input" data-rf="type" aria-label="Match as"></select>'
                +   '<select class="rf-input" data-rf="operator" aria-label="Operator"></select>'
                + '</div>'
                + '<div class="rf-value" data-rf="value"></div>';

            renderTypes(card, rule.type);
            renderOperators(card, rule.operator);
            renderValue(card, rule.value);
            return card;
        }

        function renderTypes(card, selected) {
            card.querySelector('[data-rf="type"]').innerHTML =
                options(ctx.types.map(t => ({ value: t, text: ctx.typeLabel(t) })), selected || '', 'Match as…');
        }

        /** Operators for the card's type; keeps the current one when still valid, else the first. */
        function renderOperators(card, wanted) {
            const ops = ctx.operatorsFor(card.querySelector('[data-rf="type"]').value);
            const select = card.querySelector('[data-rf="operator"]');
            const pick = ops.indexOf(wanted) !== -1 ? wanted : (ops[0] || '');
            select.innerHTML = options(ops.map(o => ({ value: o, text: ctx.operatorLabel(o) })), pick, ops.length ? '' : 'Operator…');
            select.disabled = !ops.length;
        }

        function renderValue(card, value) {
            const type = card.querySelector('[data-rf="type"]').value;
            const operator = card.querySelector('[data-rf="operator"]').value;
            const kind = ctx.valueKind(type, operator);
            const slot = card.querySelector('[data-rf="value"]');
            const esc = ctx.esc;
            card.dataset.kind = type ? kind : '';

            if (!type) { slot.innerHTML = ''; return; }

            switch (kind) {
                case 'none':
                    slot.innerHTML = '';
                    break;
                case 'choices': {
                    const wanted = Array.isArray(value) ? value.map(String) : [];
                    const choices = ctx.choicesFor(card.querySelector('[data-rf="question"]').value);
                    const long = choices.length > LONG_LIST;
                    slot.innerHTML = !choices.length
                        ? '<p class="rf-note">This question has no choices to pick from.</p>'
                        : (long ? '<input type="search" class="rf-input rf-chip-search" data-rf="chip-search" placeholder="Search ' + choices.length + ' choices…">' : '')
                        + '<div class="rf-chips' + (long ? ' is-long' : '') + '">' + choices.map(c => chip(c.value, c.text, wanted.includes(c.value))).join('') + '</div>';
                    break;
                }
                case 'range':
                    slot.innerHTML = '<div class="rf-rule-row">'
                        + '<input type="number" class="rf-input" data-rf="min" placeholder="Min" value="' + esc(value && value.min || '') + '">'
                        + '<input type="number" class="rf-input" data-rf="max" placeholder="Max" value="' + esc(value && value.max || '') + '">'
                        + '</div>';
                    break;
                case 'list':
                    slot.innerHTML = '<input type="text" class="rf-input" data-rf="text" placeholder="Words, comma-separated" value="'
                        + esc((Array.isArray(value) ? value : []).join(', ')) + '">';
                    break;
                default:
                    slot.innerHTML = '<input type="' + (kind === 'number' ? 'number' : 'text') + '" class="rf-input" data-rf="text" placeholder="Value" value="'
                        + esc(value == null || typeof value === 'object' ? '' : value) + '">';
            }
        }

        function readRule(card) {
            const q = sel => card.querySelector(sel);
            const type = q('[data-rf="type"]').value;
            const operator = q('[data-rf="operator"]').value;
            let value;

            switch (ctx.valueKind(type, operator)) {
                case 'none':    value = null; break;
                case 'choices': value = Array.from(card.querySelectorAll('.rf-chip.is-on')).map(b => b.dataset.rfValue); break;
                case 'range':   value = { min: q('[data-rf="min"]')?.value || '', max: q('[data-rf="max"]')?.value || '' }; break;
                default:        value = q('[data-rf="text"]')?.value || '';
            }

            return { question: q('[data-rf="question"]').value, type, operator, value };
        }

        /* ------------------------------------------------------------------ *
         |  Events                                                             |
         * ------------------------------------------------------------------ */

        function onClick(e) {
            const t = e.target;

            const toggle = t.closest('.rf-chip');
            if (toggle) {
                const on = !toggle.classList.contains('is-on');
                toggle.classList.toggle('is-on', on);
                toggle.setAttribute('aria-pressed', on ? 'true' : 'false');
                ctx.changed();
                return;
            }

            if (t.closest('[data-rf="range-clear"]')) {
                if (range.fp) range.fp.clear();
                writeRange('', '');
                ctx.changed();
                return;
            }

            if (t.closest('[data-rf="add"]')) {
                const card = ruleCard({});
                rulesEl.appendChild(card);
                card.querySelector('[data-rf="question"]').focus();
                return;
            }

            const remove = t.closest('[data-rf="remove"]');
            if (remove) {
                remove.closest('.rf-rule').remove();
                ctx.changed();
            }
        }

        function onInput(e) {
            if (!e.target.matches('[data-rf="chip-search"]')) return;
            const term = e.target.value.trim().toLowerCase();
            e.target.nextElementSibling.querySelectorAll('.rf-chip').forEach(b => {
                b.hidden = !!term && b.textContent.toLowerCase().indexOf(term) === -1 && !b.classList.contains('is-on');
            });
        }

        function onChange(e) {
            const t = e.target;
            if (t.matches('[data-rf="chip-search"]')) return;
            if (t.closest('[data-rf="flags"]')) { renderFlagLabel(); ctx.changed(); return; }
            const card = t.closest('.rf-rule');

            if (card && t.matches('[data-rf="question"]')) {
                // a new question gets the type that suits it
                const suggested = ctx.suggestType(t.value);
                if (suggested) card.querySelector('[data-rf="type"]').value = suggested;
                renderOperators(card, card.querySelector('[data-rf="operator"]').value);
                renderValue(card, null);
            } else if (card && t.matches('[data-rf="type"]')) {
                renderOperators(card, card.querySelector('[data-rf="operator"]').value);
                renderValue(card, null);
            } else if (card && t.matches('[data-rf="operator"]')) {
                const prev = readRule(card).value;
                renderValue(card, prev);
            }

            ctx.changed();
        }

        return {
            mount(context) {
                ctx = context;
                root.classList.add('rf-panel');
                renderShell();
                root.addEventListener('click', onClick);
                root.addEventListener('change', onChange);
                root.addEventListener('input', onInput);
            },

            read() {
                return {
                    from_date: range.from,
                    to_date: range.to,
                    flag: selectedFlags(),
                    rules: Array.from(rulesEl.children).map(readRule),
                };
            },

            write(state) {
                writeRange(state.from_date, state.to_date);

                const menu = $('[data-rf="flags"]');
                if (menu) menu.querySelectorAll('input').forEach(i => { i.checked = state.flag.includes(i.value); });
                renderFlagLabel();

                rulesEl.innerHTML = '';
                state.rules.forEach(rule => rulesEl.appendChild(ruleCard(rule)));
            },

            destroy() {
                if (range.fp) range.fp.destroy();
                root.removeEventListener('click', onClick);
                root.removeEventListener('change', onChange);
                root.removeEventListener('input', onInput);
            },
        };
    }

    return { create };
})();
