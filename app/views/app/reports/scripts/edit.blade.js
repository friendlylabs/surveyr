/**
 * Report editor
 * --------------------------------------------------------------------------
 * Three panes: outline rail, section canvas, inspector. The document is a v2
 * ReportDoc kept in memory; every mutation goes through commit(), which marks
 * the draft dirty, re-renders only what changed and schedules an autosave.
 *
 * A report draws on several source forms. Each attached form has a context
 * (survey model, viz rules, submissions, filters) in `ctxs`, keyed by form id;
 * every attachment names the form it charts.
 *
 * Charts are mounted through ReportViz and kept alive across re-renders: a
 * section card is rebuilt around its existing chart nodes, so editing text
 * never re-plots anything.
 *
 * Inline answers: paragraphs may carry `query:{…}` tokens, answered by
 * SurveyQL over named sources/expressions (`svql`, see ReportQuery). They are
 * re-evaluated, debounced, whenever text, queries or data change.
 */
(function (global) {
    'use strict';

    const cfg = global.reportEditorConfig;
    const esc = ReportDoc.escapeHtml;

    const HEADERS = () => ({
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': csrf_token,
    });

    /* ---------------------------------------------------------------------- *
     |  State                                                                 |
     * ---------------------------------------------------------------------- */

    /** form id -> { id, title, survey, rules, flags, data } */
    const ctxs = {};
    /** attached form ids, in rail order */
    const order = [];
    /** forms the user could still attach: [{id, title}] */
    let available = Array.isArray(cfg.availableForms) ? cfg.availableForms.slice() : [];

    function addContext(bundle) {
        const survey = new Survey.Model(bundle.survey || {});
        ctxs[bundle.id] = {
            id:      bundle.id,
            title:   bundle.title,
            slug:    bundle.slug || null,
            updated: bundle.updated || null,
            json:    bundle.survey || {},
            survey:  survey,
            rules:   VizEngine.normalize(bundle.rules, survey.getAllQuestions()),
            flags:   bundle.flags || [],
            data:    Array.isArray(bundle.data) ? bundle.data : [],
        };
        if (order.indexOf(bundle.id) === -1) order.push(bundle.id);
        return ctxs[bundle.id];
    }

    (Array.isArray(cfg.forms) ? cfg.forms : []).forEach(addContext);

    const doc = ReportDoc.normalize(cfg.doc);

    /** SurveyQL sources/expressions; saved with every draft save */
    const svql = ReportQuery.normalize(cfg.svql);
    /** latest ReportQuery.evaluate() output, null until the first run */
    let queryResults = null;

    const state = {
        sel:       { section: null, att: null },
        dirty:     false,
        saving:    false,
        error:     null,
        savedAt:   null,
        published: !!cfg.isPublished,
        preview:   false,
    };

    /** attachment id -> ReportViz handle */
    const mounted = new Map();

    const el = {
        studio:     document.getElementById('reportStudio'),
        canvas:     document.getElementById('reportCanvas'),
        outline:    document.getElementById('reportOutline'),
        sources:    document.getElementById('reportSources'),
        addForm:    document.getElementById('addFormBtn'),
        inspector:  document.getElementById('reportInspector'),
        rail:       document.querySelector('#reportStudio .rp-rail'),
        meta:       document.getElementById('reportMeta'),
        status:     document.getElementById('reportStatus'),
        publish:    document.getElementById('publishBtn'),
        preview:    document.getElementById('reportPreview'),
        previewBtn: document.getElementById('togglePreview'),
    };

    /* ---------------------------------------------------------------------- *
     |  Lookups                                                               |
     * ---------------------------------------------------------------------- */

    const sectionById = id => doc.sections.find(s => s.id === id) || null;
    const sectionIndex = id => doc.sections.findIndex(s => s.id === id);

    function attachmentById(id) {
        for (const section of doc.sections) {
            const att = section.attachments.find(a => a.id === id);
            if (att) return { section, att };
        }
        return null;
    }

    const selectedSection = () => sectionById(state.sel.section);
    const ctxOf = formId => ctxs[formId] || null;
    const formTitle = formId => (ctxs[formId] ? ctxs[formId].title : 'Form removed');

    function questionTitle(att) {
        const ctx = ctxOf(att.form);
        const q = ctx ? ctx.survey.getQuestionByName(att.question) : null;
        return q ? (q.title || q.name) : att.question;
    }

    function responses(att) {
        const ctx = ctxOf(att.form);
        return ctx ? ReportDoc.responsesFor(att.question, ctx.data) : 0;
    }

    const chartLabel = type => VizEngine.CHART_LABELS[type] || type;
    const visualizerLabel = type => (VizEngine.VISUALIZERS[type] || {}).label || type;

    /* ---------------------------------------------------------------------- *
     |  Commit / autosave                                                     |
     * ---------------------------------------------------------------------- */

    let saveTimer = null;

    function scheduleSave() {
        clearTimeout(saveTimer);
        saveTimer = setTimeout(() => save(false), 1500);
    }

    function markDirty() {
        state.dirty = true;
        state.error = null;
        // the draft now differs from the published copy
        if (state.published) { state.published = false; renderStatusPill(); }
    }

    /**
     * Record a change. `scope` says what to redraw:
     *   'text'          nothing structural: outline + meta only
     *   'section:<id>'  rebuild that card (layout or attachments changed)
     *   'all'           rebuild the canvas
     */
    function commit(scope) {
        markDirty();

        if (scope === 'all') renderCanvas();
        else if (scope && scope.indexOf('section:') === 0) renderSection(scope.slice(8));

        renderOutline();
        renderSources();
        renderInspector();
        renderMeta();
        scheduleSave();
    }

    function save(publish) {
        clearTimeout(saveTimer);
        if (state.saving) {
            // a save is in flight; queue another pass right after it
            saveTimer = setTimeout(() => save(publish), 500);
            return Promise.resolve();
        }

        state.saving = true;
        renderMeta();
        if (publish) el.publish.disabled = true;

        return fetch(cfg.routes.build, {
            method: 'POST',
            headers: HEADERS(),
            body: JSON.stringify({ content: doc, filters: compileAllFilters(), forms: order.slice(), svql: svql, publish: publish ? 1 : 0 }),
        })
            .then(r => r.json())
            .then(res => {
                if (!res.status) throw new Error(res.message || 'Save failed');
                state.dirty = false;
                state.savedAt = new Date();
                if (publish) {
                    state.published = true;
                    renderStatusPill();
                    toast.success({ message: res.message });
                }
            })
            .catch(err => {
                state.error = err.message || 'Save failed';
                if (publish) toast.error({ message: state.error });
            })
            .finally(() => {
                state.saving = false;
                el.publish.disabled = false;
                renderMeta();
            });
    }

    global.addEventListener('beforeunload', e => {
        if (!state.dirty && !state.saving) return;
        e.preventDefault();
        e.returnValue = '';
    });

    /* ---------------------------------------------------------------------- *
     |  Top bar                                                               |
     * ---------------------------------------------------------------------- */

    function renderMeta() {
        const n = doc.sections.length;
        const f = order.length;
        let status;
        el.meta.className = 'rp-meta';

        if (state.error) { status = state.error; el.meta.classList.add('is-error'); }
        else if (state.saving) status = 'Saving…';
        else if (state.dirty) { status = 'Unsaved changes'; el.meta.classList.add('is-dirty'); }
        else if (state.savedAt) status = 'Saved ' + state.savedAt.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        else status = 'All changes saved';

        el.meta.textContent = f + (f === 1 ? ' form' : ' forms') + ' · ' + n + (n === 1 ? ' section' : ' sections') + ' · ' + status;
    }

    /* preview mode: render the in-memory draft through the viewer's renderer */

    let previewHandles = [];

    function setPreview(on) {
        state.preview = on;
        el.studio.classList.toggle('is-preview', on);
        el.previewBtn.classList.toggle('is-primary', on);
        el.previewBtn.innerHTML = on ? '<i class="fa-solid fa-pencil"></i>' : '<i class="fa-solid fa-eye"></i>';
        setTip(el.previewBtn, on ? 'Back to editing' : 'Preview the draft');

        previewHandles.forEach(h => h.destroy());
        previewHandles = [];

        if (on) {
            el.preview.hidden = false;
            previewHandles = ReportRenderer.render(el.preview, doc, ctxs, {
                title: cfg.title,
                emptyHtml: emptyHtml(),
                queries: queryResults,
                queryErrors: true,
                queryPending: queriesRunning,
            });
            el.preview.scrollTop = 0;
        } else {
            el.preview.hidden = true;
            el.preview.innerHTML = '';
            resizeAll();
        }
    }

    el.previewBtn.addEventListener('click', () => setPreview(!state.preview));

    function renderStatusPill() {
        el.status.textContent = state.published ? 'Published' : 'Draft';
        el.status.title = state.published ? 'Readers see this version' : 'Unpublished changes';
        el.status.classList.toggle('is-published', state.published);
    }

    el.publish.addEventListener('click', () => save(true));

    function pane(name, open) {
        el.studio.classList.toggle(name + '-open', open);
        document.getElementById(name === 'rail' ? 'toggleRail' : 'toggleInspector').classList.toggle('is-on', open);
        resizeAll();
    }

    // the panes slide (see editor.blade.css); charts refit once the canvas settles
    [el.rail, el.inspector].forEach(p => p.addEventListener('transitionend', e => {
        if (e.target === p && e.propertyName.indexOf('margin') === 0) resizeAll();
    }));

    document.getElementById('toggleRail').addEventListener('click', () => pane('rail', !el.studio.classList.contains('rail-open')));
    document.getElementById('toggleInspector').addEventListener('click', () => pane('inspector', !el.studio.classList.contains('inspector-open')));

    function resizeAll() {
        requestAnimationFrame(() => mounted.forEach(h => h.resize()));
    }

    /* ---------------------------------------------------------------------- *
     |  Rail: source forms + outline                                          |
     * ---------------------------------------------------------------------- */

    /** Form ids read by a query source. */
    const queriedForms = () => svql.sources.map(s => s.form).filter(Boolean);

    function renderSources() {
        const used = ReportDoc.formsUsed(doc);
        const queried = queriedForms();

        el.sources.innerHTML = order.map(id => {
            const why = used.indexOf(id) !== -1 ? 'Remove its visuals first'
                : queried.indexOf(id) !== -1 ? 'Remove its query sources first' : null;
            return '<span class="rp-source" title="' + esc(formTitle(id)) + '">'
                +   '<i class="fa-solid fa-circle"></i>'
                +   '<span>' + esc(formTitle(id)) + '</span>'
                +   '<button type="button" class="rp-source-x" data-remove-form="' + id + '"' + (why ? ' disabled title="' + why + '"' : ' title="Remove form"') + '>'
                +     '<i class="fa-solid fa-xmark"></i>'
                +   '</button>'
                + '</span>';
        }).join('');

        el.addForm.disabled = !available.length;
        setTip(el.addForm, available.length ? 'Add form' : 'No other forms available');
    }

    el.sources.addEventListener('click', e => {
        const remove = e.target.closest('[data-remove-form]');
        if (remove && !remove.disabled) removeForm(parseInt(remove.dataset.removeForm, 10));
    });

    el.addForm.addEventListener('click', openFormPicker);

    function renderOutline() {
        if (!doc.sections.length) {
            el.outline.innerHTML = '<span class="rp-outline-empty">No sections yet.</span>';
            return;
        }

        el.outline.innerHTML = doc.sections.map((s, i) =>
            '<button type="button" class="rp-outline-item' + (s.id === state.sel.section ? ' is-active' : '') + '" data-goto="' + esc(s.id) + '">'
            +   '<span class="rp-outline-n">' + (i + 1) + '</span>'
            +   '<span class="min-w-0">'
            +     '<span class="rp-outline-title">' + esc(s.title || 'Untitled section') + '</span>'
            +     '<span class="rp-outline-kind">' + esc(ReportDoc.attachmentLabel(s)) + '</span>'
            +   '</span>'
            + '</button>'
        ).join('');
    }

    el.outline.addEventListener('click', e => {
        const item = e.target.closest('[data-goto]');
        if (!item) return;
        select(item.dataset.goto, null);
        const card = cardEl(item.dataset.goto);
        if (card) card.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });

    /* form picker (add source forms): at most 5 rows, the latest by default, search for the rest */

    const MAX_FORMS = 5;
    const formPickerEl = document.getElementById('addFormModal');
    const formPickerList = document.getElementById('addFormList');
    const formPickerSearch = document.getElementById('addFormSearch');
    const formPickerHeading = document.getElementById('addFormHeading');
    const formPickerCount = document.getElementById('addFormCount');
    const formPickerSubmit = document.getElementById('addFormSubmit');
    const pickedForms = new Set();

    const byUpdated = (a, b) => (b.updated || '').localeCompare(a.updated || '');

    function renderFormPicker() {
        const term = formPickerSearch.value.trim().toLowerCase();
        const matches = term ? available.filter(f => f.title.toLowerCase().indexOf(term) !== -1) : available;
        const list = matches.slice(0, MAX_FORMS);

        formPickerHeading.textContent = term
            ? matches.length + (matches.length === 1 ? ' match' : ' matches')
            : 'Recently updated';

        const empty = available.length ? 'No forms match your search.' : 'Every form you can access is already part of this report.';

        formPickerList.innerHTML = list.map(f =>
            '<label class="rp-form-row">'
            +   '<input type="checkbox" class="form-check-input" value="' + f.id + '"' + (pickedForms.has(f.id) ? ' checked' : '') + '>'
            +   '<span>' + esc(f.title) + '</span>'
            + '</label>'
        ).join('') || '<p class="text-body-tertiary small mb-0">' + empty + '</p>';

        if (matches.length > MAX_FORMS) {
            const rest = matches.length - MAX_FORMS;
            const hint = term
                ? 'Showing ' + MAX_FORMS + ' of ' + matches.length + '. Refine your search to narrow it down.'
                : 'Search to find ' + (rest === 1 ? 'your other form.' : 'your other ' + rest + ' forms.');
            formPickerList.innerHTML += '<p class="text-body-tertiary small mb-0 mt-1">' + hint + '</p>';
        }

        renderFormPickerFooter();
    }

    function renderFormPickerFooter() {
        const n = pickedForms.size;
        formPickerCount.textContent = n ? n + ' selected' : 'No forms selected';
        formPickerSubmit.textContent = n > 1 ? 'Add ' + n + ' forms' : 'Add';
        formPickerSubmit.disabled = !n;
    }

    function openFormPicker() {
        pickedForms.clear();
        formPickerSearch.value = '';
        renderFormPicker();
        bootstrap.Modal.getOrCreateInstance(formPickerEl).show();
    }

    formPickerEl.addEventListener('shown.bs.modal', () => formPickerSearch.focus());
    formPickerSearch.addEventListener('input', renderFormPicker);

    formPickerList.addEventListener('change', e => {
        const box = e.target.closest('input[type="checkbox"]');
        if (!box) return;
        const id = parseInt(box.value, 10);
        if (box.checked) pickedForms.add(id); else pickedForms.delete(id);
        renderFormPickerFooter();
    });

    formPickerSubmit.addEventListener('click', () => {
        if (!pickedForms.size) return;
        formPickerSubmit.disabled = true;
        addForms(Array.from(pickedForms))
            .then(ok => { if (ok) bootstrap.Modal.getOrCreateInstance(formPickerEl).hide(); })
            .finally(renderFormPickerFooter);
    });

    function addForms(formIds) {
        return fetch(cfg.routes.forms, { method: 'POST', headers: HEADERS(), body: JSON.stringify({ add: formIds }) })
            .then(r => r.json())
            .then(res => {
                if (!res.status || !Array.isArray(res.forms)) throw new Error(res.message || 'Could not add the forms');

                res.forms.forEach(bundle => {
                    addContext(bundle);
                    filterBuilderFor(bundle.id, {});
                });
                available = available.filter(f => formIds.indexOf(f.id) === -1);
                pickedForms.clear();

                renderSources();
                renderPicker();
                renderFilterSwitcher();
                renderQuerySources();
                renderMeta();
                refreshQueries();
                toast.success({ message: res.forms.length === 1 ? res.forms[0].title + ' added' : res.forms.length + ' forms added' });
                return true;
            })
            .catch(err => { toast.error({ message: err.message || 'Could not add the forms' }); return false; });
    }

    function removeForm(formId) {
        if (ReportDoc.formsUsed(doc).indexOf(formId) !== -1) {
            toast.error({ message: 'Remove the visuals from this form before detaching it' });
            return;
        }
        if (queriedForms().indexOf(formId) !== -1) {
            toast.error({ message: 'Remove the query sources that read this form before detaching it' });
            return;
        }

        const title = formTitle(formId);
        const updated = ctxs[formId] ? ctxs[formId].updated : null;
        fetch(cfg.routes.forms, { method: 'POST', headers: HEADERS(), body: JSON.stringify({ remove: formId }) })
            .then(r => r.json())
            .then(res => {
                if (!res.status) throw new Error(res.message || 'Could not remove the form');

                delete ctxs[formId];
                order.splice(order.indexOf(formId), 1);
                available.push({ id: formId, title, updated });
                available.sort(byUpdated);
                destroyFilterBuilder(formId);

                renderSources();
                renderPicker();
                renderFilterSwitcher();
                renderQuerySources();
                renderMeta();
                toast.success({ message: title + ' removed' });
            })
            .catch(err => toast.error({ message: err.message || 'Could not remove the form' }));
    }

    /* ---------------------------------------------------------------------- *
     |  Selection                                                             |
     * ---------------------------------------------------------------------- */

    function select(sectionId, attId) {
        if (state.sel.section === sectionId && state.sel.att === attId) return;
        state.sel = { section: sectionId, att: attId };

        el.canvas.querySelectorAll('.rp-card').forEach(c => c.classList.toggle('is-active', c.dataset.section === sectionId));
        el.canvas.querySelectorAll('.rp-att').forEach(a => a.classList.toggle('is-active', a.dataset.att === attId));

        renderOutline();
        renderInspector();
        focusChanged();
    }

    /** Drop the focus: no section highlighted, inspector tucked away. */
    const blur = () => select(null, null);

    /** The inspector only shows while a section has the focus. */
    let lastFocused = null, swapTimer = null;

    function focusChanged() {
        pane('inspector', !!state.sel.section);

        // ease the inspector content in when the focus moves to another section
        if (state.sel.section && state.sel.section !== lastFocused) {
            el.inspector.classList.remove('is-swap');
            void el.inspector.offsetWidth;
            el.inspector.classList.add('is-swap');
            clearTimeout(swapTimer);
            swapTimer = setTimeout(() => el.inspector.classList.remove('is-swap'), 300);
        }
        lastFocused = state.sel.section;
    }

    /* ---------------------------------------------------------------------- *
     |  Canvas                                                                |
     * ---------------------------------------------------------------------- */

    const cardEl = id => el.canvas.querySelector('.rp-card[data-section="' + id + '"]');

    function attachmentHtml(att) {
        const n = responses(att);
        return '<div class="rp-att' + (att.id === state.sel.att ? ' is-active' : '') + '" data-att="' + esc(att.id) + '">'
            +   '<div class="rp-att-head">'
            +     '<span class="rp-att-title">' + esc(questionTitle(att)) + '</span>'
            +     '<button type="button" class="rp-icon-btn is-danger" data-remove-att="' + esc(att.id) + '" title="Remove visual"><i class="fa-solid fa-xmark"></i></button>'
            +   '</div>'
            +   '<div class="rp-att-tools"><span class="rp-att-n">' + n + ' response' + (n === 1 ? '' : 's') + '</span></div>'
            +   '<div class="rp-att-slot"></div>'
            +   '<span class="rp-att-foot">' + esc(formTitle(att.form)) + '</span>'
            + '</div>';
    }

    function cardHtml(section, index) {
        const layout = ReportDoc.layoutClass(section);
        const atts = section.type === 'narrative' ? [] : section.attachments;
        const lead = (layout === 'rp-l-side' || layout === 'rp-l-mixed') ? atts[0] : null;
        const rest = layout === 'rp-l-stack' ? atts : layout === 'rp-l-mixed' ? atts.slice(1) : [];

        return '<article class="rp-card' + (section.id === state.sel.section ? ' is-active' : '') + '" data-section="' + esc(section.id) + '">'
            +   '<header class="rp-card-head">'
            +     '<span class="rp-drag" draggable="true" title="Drag to reorder"><i class="fa-solid fa-grip-vertical"></i></span>'
            +     '<span class="rp-num">' + (index + 1) + '</span>'
            +     '<input class="rp-title-input" type="text" value="' + esc(section.title) + '" placeholder="Section title" data-title="' + esc(section.id) + '">'
            +     '<span class="rp-pill">' + esc(ReportDoc.attachmentLabel(section)) + '</span>'
            +     '<button type="button" class="rp-icon-btn" data-duplicate="' + esc(section.id) + '" title="Duplicate section"><i class="fa-regular fa-clone"></i></button>'
            +     '<button type="button" class="rp-icon-btn is-danger" data-delete="' + esc(section.id) + '" title="Remove section"><i class="fa-regular fa-trash-can"></i></button>'
            +   '</header>'
            +   '<div class="rp-card-body">'
            +     '<div class="rp-grid ' + layout + '">'
            +       '<div class="rp-text"><textarea class="rp-body-input" data-body="' + esc(section.id) + '" placeholder="Write the narrative for this section…">' + esc(ReportDoc.paragraphsToText(section.body)) + '</textarea>'
            +         '<div class="rp-q-hints" data-q-hints="' + esc(section.id) + '">' + queryHintsHtml(section) + '</div></div>'
            +       (lead ? attachmentHtml(lead) : '')
            +       (rest.length ? '<div class="rp-atts-rest">' + rest.map(attachmentHtml).join('') + '</div>' : '')
            +     '</div>'
            +     (section.type === 'visual'
                    ? '<div class="rp-card-foot"><button type="button" class="rp-btn is-dashed" data-attach="' + esc(section.id) + '"><i class="fa-solid fa-plus"></i>Attach visualisation</button></div>'
                    : '')
            +   '</div>'
            + '</article>';
    }

    function emptyHtml() {
        return '<div class="rp-empty">'
            +   '<span class="rp-empty-icon"><i class="fa-solid fa-align-left"></i></span>'
            +   '<span class="rp-empty-title">No sections yet</span>'
            +   '<span class="rp-empty-text">Add a narrative block or a section with charts from any source form to start writing this report.</span>'
            + '</div>';
    }

    function addSectionHtml() {
        return '<div class="rp-add-wrap dropdown">'
            +   '<button type="button" class="rp-btn is-dashed" data-bs-toggle="dropdown" aria-expanded="false"><i class="fa-solid fa-plus"></i>Add section</button>'
            +   '<ul class="dropdown-menu">'
            +     '<li><button type="button" class="dropdown-item" data-add="narrative"><i class="fa-solid fa-align-left me-2 text-body-tertiary"></i>Narrative section</button></li>'
            +     '<li><button type="button" class="dropdown-item" data-add="visual"><i class="fa-solid fa-chart-simple me-2 text-body-tertiary"></i>Section with charts</button></li>'
            +   '</ul>'
            + '</div>';
    }

    /** Full canvas rebuild; chart nodes survive through mountAttachments(). */
    function renderCanvas() {
        const parked = park();

        el.canvas.innerHTML = (doc.sections.length
            ? doc.sections.map(cardHtml).join('')
            : emptyHtml()) + addSectionHtml();

        doc.sections.forEach(s => mountAttachments(s, parked));
        dropOrphans(parked);
        el.canvas.querySelectorAll('textarea.rp-body-input').forEach(autosize);
    }

    /** Rebuild one card in place. */
    function renderSection(id) {
        const section = sectionById(id);
        const card = cardEl(id);
        if (!section || !card) return renderCanvas();

        const parked = park(card);
        card.insertAdjacentHTML('afterend', cardHtml(section, sectionIndex(id)));
        card.remove();

        mountAttachments(section, parked);
        dropOrphans(parked);
        cardEl(id).querySelectorAll('textarea.rp-body-input').forEach(autosize);
    }

    /**
     * Lift live chart nodes out of the DOM before it is rebuilt, so they can
     * be re-inserted into the fresh slots instead of re-rendered.
     */
    function park(root) {
        const parked = new Map();
        (root || el.canvas).querySelectorAll('.rp-att-slot').forEach(slot => {
            const attEl = slot.closest('.rp-att');
            const handle = attEl && mounted.get(attEl.dataset.att);
            if (handle && handle.el === slot) {
                slot.remove();
                parked.set(handle.id, handle);
            }
        });
        return parked;
    }

    function mountAttachments(section, parked) {
        if (section.type === 'narrative') return;

        section.attachments.forEach(att => {
            const attEl = el.canvas.querySelector('.rp-att[data-att="' + att.id + '"]');
            if (!attEl) return;
            const slot = attEl.querySelector('.rp-att-slot');

            let handle = parked.get(att.id);
            if (handle) {
                slot.replaceWith(handle.el);
                parked.delete(att.id);
                handle.resize();
            } else {
                handle = ReportViz.mount(slot, ReportDoc.resolveAttachment(att, ctxs), ctxOf(att.form));
                mounted.set(att.id, handle);
            }

            renderAttachmentTools(attEl, att, handle);
        });
    }

    /** Destroy handles whose attachments are gone from the document. */
    function dropOrphans(parked) {
        parked.forEach((handle, id) => { handle.destroy(); mounted.delete(id); });
        mounted.forEach((handle, id) => {
            if (!attachmentById(id)) { handle.destroy(); mounted.delete(id); }
        });
    }

    /** Chart type + sort controls, driven by what the mounted visualizer offers. */
    function renderAttachmentTools(attEl, att, handle) {
        const tools = attEl.querySelector('.rp-att-tools');
        const n = tools.querySelector('.rp-att-n');
        const types = handle.chartTypes();
        const current = handle.chartType();

        let html = '';
        if (types.length > 1) {
            html += '<span class="rp-seg">' + types.map(t =>
                '<button type="button" class="' + (t === current ? 'is-on' : '') + '" data-chart="' + esc(t) + '">' + esc(chartLabel(t)) + '</button>'
            ).join('') + '</span>';
        }
        if (handle.supportsSort()) {
            html += '<select class="rp-select" data-sort>' + ReportDoc.SORTS.map(s =>
                '<option value="' + s + '"' + (s === att.sort ? ' selected' : '') + '>' + esc(ReportDoc.SORT_LABELS[s]) + '</option>'
            ).join('') + '</select>';
        }

        tools.innerHTML = html;
        tools.appendChild(n);
    }

    function autosize(textarea) {
        textarea.style.height = 'auto';
        textarea.style.height = (textarea.scrollHeight + 2) + 'px';
    }

    /* canvas events (delegated) */

    el.canvas.addEventListener('input', e => {
        const t = e.target;

        if (t.matches('[data-title]')) {
            const section = sectionById(t.dataset.title);
            if (!section) return;
            section.title = t.value;
            commit('text');
        }

        if (t.matches('[data-body]')) {
            const section = sectionById(t.dataset.body);
            if (!section) return;
            section.body = ReportDoc.textToParagraphs(t.value);
            autosize(t);
            commit('text');
            renderQueryHints(section.id);
            refreshQueries();
        }
    });

    el.canvas.addEventListener('keydown', e => {
        if (e.target.matches('[data-title]') && e.key === 'Enter') {
            e.preventDefault();
            const body = cardEl(e.target.dataset.title).querySelector('[data-body]');
            if (body) body.focus();
        }
    });

    el.canvas.addEventListener('change', e => {
        const t = e.target;
        if (!t.matches('[data-sort]')) return;

        const attEl = t.closest('.rp-att');
        const found = attachmentById(attEl.dataset.att);
        if (!found) return;

        found.att.sort = t.value;
        const handle = mounted.get(found.att.id);
        if (handle) handle.setSort(t.value);
        commit('text');
    });

    el.canvas.addEventListener('click', e => {
        const t = e.target;

        const chart = t.closest('[data-chart]');
        if (chart) {
            const attEl = chart.closest('.rp-att');
            select(attEl.closest('.rp-card').dataset.section, attEl.dataset.att);
            setChartType(attEl.dataset.att, chart.dataset.chart);
            return;
        }

        const remove = t.closest('[data-remove-att]');
        if (remove) { e.stopPropagation(); removeAttachment(remove.dataset.removeAtt); return; }

        const attach = t.closest('[data-attach]');
        if (attach) { openAttachPicker(attach.dataset.attach); return; }

        const dup = t.closest('[data-duplicate]');
        if (dup) { duplicateSection(dup.dataset.duplicate); return; }

        const del = t.closest('[data-delete]');
        if (del) { deleteSection(del.dataset.delete); return; }

        const add = t.closest('[data-add]');
        if (add) { addSection(add.dataset.add); return; }

        // plain click: select the section and, if inside one, the attachment;
        // a click on the canvas background drops the focus
        const attEl = t.closest('.rp-att');
        const card = t.closest('.rp-card');
        if (card) select(card.dataset.section, attEl ? attEl.dataset.att : null);
        else if (!t.closest('.rp-add-wrap')) blur();
    });

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape' && state.sel.section && !document.querySelector('.offcanvas.show, .modal.show, .swal2-container')) {
            if (document.activeElement && el.canvas.contains(document.activeElement)) document.activeElement.blur();
            blur();
        }
    });

    el.canvas.addEventListener('focusin', e => {
        const card = e.target.closest('.rp-card');
        if (card && state.sel.section !== card.dataset.section) select(card.dataset.section, null);
    });

    /* drag to reorder */

    let dragId = null;

    el.canvas.addEventListener('dragstart', e => {
        const node = e.target.nodeType === 1 ? e.target : e.target.parentElement;
        const handle = node && node.closest('.rp-drag');
        if (!handle) return;                      // text selection drags stay native
        const card = handle.closest('.rp-card');
        dragId = card.dataset.section;
        card.classList.add('is-dragging');
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', dragId);
        e.dataTransfer.setDragImage(card, 20, 20);
    });

    function clearDropMarks() {
        el.canvas.querySelectorAll('.drop-before, .drop-after').forEach(c => c.classList.remove('drop-before', 'drop-after'));
    }

    el.canvas.addEventListener('dragover', e => {
        if (!dragId) return;
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';

        const card = e.target.closest('.rp-card');
        clearDropMarks();
        if (!card || card.dataset.section === dragId) return;

        const box = card.getBoundingClientRect();
        card.classList.add(e.clientY < box.top + box.height / 2 ? 'drop-before' : 'drop-after');
    });

    el.canvas.addEventListener('drop', e => {
        if (!dragId) return;
        e.preventDefault();

        const target = e.target.closest('.rp-card');
        const before = target && target.classList.contains('drop-before');
        clearDropMarks();

        if (!target || target.dataset.section === dragId) return;

        const from = sectionIndex(dragId);
        const [moved] = doc.sections.splice(from, 1);
        let to = sectionIndex(target.dataset.section);
        if (!before) to += 1;
        doc.sections.splice(to, 0, moved);

        commit('all');
    });

    el.canvas.addEventListener('dragend', () => {
        const card = dragId && cardEl(dragId);
        if (card) card.classList.remove('is-dragging');
        clearDropMarks();
        dragId = null;
    });

    /* ---------------------------------------------------------------------- *
     |  Section operations                                                    |
     * ---------------------------------------------------------------------- */

    function addSection(type) {
        const section = ReportDoc.newSection(type);
        doc.sections.push(section);
        state.sel = { section: section.id, att: null };
        commit('all');
        focusChanged();

        const card = cardEl(section.id);
        if (card) {
            card.scrollIntoView({ behavior: 'smooth', block: 'start' });
            card.querySelector('[data-title]').focus();
        }
    }

    function duplicateSection(id) {
        const index = sectionIndex(id);
        if (index === -1) return;

        const copy = ReportDoc.cloneSection(doc.sections[index]);
        doc.sections.splice(index + 1, 0, copy);
        state.sel = { section: copy.id, att: null };
        commit('all');
        focusChanged();
    }

    function deleteSection(id) {
        const section = sectionById(id);
        if (!section) return;

        Swal.fire({
            title: 'Remove this section?',
            text: (section.title || 'Untitled section') + ' and its attached visuals will be removed from the draft.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d9484a',
            confirmButtonText: 'Remove',
            cancelButtonText: 'Keep',
        }).then(result => {
            if (!result.isConfirmed) return;

            const index = sectionIndex(id);
            doc.sections.splice(index, 1);

            // removing a section drops the focus rather than jumping to a neighbour
            state.sel = { section: null, att: null };
            commit('all');
            focusChanged();
        });
    }

    function setSectionType(id, type) {
        const section = sectionById(id);
        if (!section || section.type === type) return;

        const apply = () => {
            section.type = type;
            if (type === 'narrative') section.attachments = [];
            if (state.sel.att) state.sel.att = null;
            commit('section:' + id);
        };

        if (type === 'narrative' && section.attachments.length) {
            Swal.fire({
                title: 'Switch to narrative?',
                text: 'The ' + section.attachments.length + ' attached visual(s) will be removed from this section.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Switch',
                cancelButtonText: 'Cancel',
            }).then(r => { if (r.isConfirmed) apply(); });
        } else {
            apply();
        }
    }

    /* ---------------------------------------------------------------------- *
     |  Attachment operations                                                 |
     * ---------------------------------------------------------------------- */

    function addAttachment(sectionId, formId, questionName) {
        const section = sectionById(sectionId);
        const ctx = ctxOf(formId);
        if (!section || !ctx) return;

        if (section.type !== 'visual') section.type = 'visual';
        const att = ReportDoc.newAttachment(formId, questionName, ctx.rules[questionName]);
        section.attachments.push(att);
        state.sel = { section: sectionId, att: att.id };
        commit('section:' + sectionId);
    }

    function removeAttachment(attId) {
        const found = attachmentById(attId);
        if (!found) return;

        found.section.attachments = found.section.attachments.filter(a => a.id !== attId);
        if (state.sel.att === attId) state.sel.att = null;
        commit('section:' + found.section.id);
    }

    function setChartType(attId, type) {
        const found = attachmentById(attId);
        const handle = mounted.get(attId);
        if (!found || !handle || !handle.setChartType(type)) return;

        found.att.chart_type = type;
        found.att.visualizer = handle.visualizer();
        const attEl = el.canvas.querySelector('.rp-att[data-att="' + attId + '"]');
        if (attEl) renderAttachmentTools(attEl, found.att, handle);
        commit('text');
    }

    function setVisualizer(attId, type) {
        const found = attachmentById(attId);
        const handle = mounted.get(attId);
        if (!found || !handle || !handle.setVisualizer(type)) return;

        found.att.visualizer = type;
        found.att.chart_type = handle.chartType();
        const attEl = el.canvas.querySelector('.rp-att[data-att="' + attId + '"]');
        if (attEl) renderAttachmentTools(attEl, found.att, handle);
        commit('text');
    }

    /* attach picker: chartable questions of every source form, grouped by form */

    let pickerTarget = null;
    const pickerEl = document.getElementById('attachPickerOffcanvas');
    const pickerList = document.getElementById('attachList');
    const pickerSearch = document.getElementById('attachSearch');

    const TYPE_ICONS = {
        radiogroup: 'fa-circle-dot', checkbox: 'fa-square-check', dropdown: 'fa-square-caret-down', tagbox: 'fa-tags',
        boolean: 'fa-toggle-on', rating: 'fa-star', ranking: 'fa-list-ol', matrix: 'fa-table-cells', matrixdropdown: 'fa-table-cells',
        text: 'fa-font', comment: 'fa-align-left', number: 'fa-hashtag', expression: 'fa-calculator', imagepicker: 'fa-image',
    };

    function renderPicker() {
        pickerList.innerHTML = order.map(formId => {
            const ctx = ctxs[formId];
            const items = ctx.survey.getAllQuestions().filter(VizEngine.isChartable).map(q => {
                const rule = ctx.rules[q.name] || {};
                const kind = VizEngine.visualizerKey(q);
                const meta = [q.getType(), rule.visualizer ? visualizerLabel(rule.visualizer) : null, rule.chart_type ? chartLabel(rule.chart_type) : null]
                    .filter(Boolean).join(' · ');
                return '<button type="button" class="rp-pick" data-pick="' + esc(q.name) + '" data-form="' + formId + '" data-search="' + esc((q.title || '') + ' ' + q.name + ' ' + ctx.title).toLowerCase() + '">'
                    +   '<i class="fa-solid ' + (TYPE_ICONS[kind] || 'fa-chart-simple') + '"></i>'
                    +   '<span class="min-w-0"><span class="rp-pick-title">' + esc(q.title || q.name) + '</span><span class="rp-pick-meta">' + esc(meta) + '</span></span>'
                    + '</button>';
            }).join('');

            return '<div class="rp-pick-group" data-pick-group="' + formId + '">'
                +   '<span class="rp-kicker">' + esc(ctx.title) + '</span>'
                +   (items || '<p class="text-body-tertiary small mb-0">No questions in this form can be charted.</p>')
                + '</div>';
        }).join('') || '<p class="text-body-tertiary small mb-0">Add a source form first.</p>';
    }

    function openAttachPicker(sectionId) {
        pickerTarget = sectionId;
        select(sectionId, state.sel.section === sectionId ? state.sel.att : null);
        pickerSearch.value = '';
        filterPicker('');
        bootstrap.Offcanvas.getOrCreateInstance(pickerEl).show();
        setTimeout(() => pickerSearch.focus(), 300);
    }

    function filterPicker(term) {
        pickerList.querySelectorAll('.rp-pick').forEach(p => p.classList.toggle('is-hidden', term && p.dataset.search.indexOf(term) === -1));
        pickerList.querySelectorAll('.rp-pick-group').forEach(g => {
            g.classList.toggle('is-hidden', term && !g.querySelector('.rp-pick:not(.is-hidden)'));
        });
    }

    pickerSearch.addEventListener('input', () => filterPicker(pickerSearch.value.trim().toLowerCase()));

    pickerList.addEventListener('click', e => {
        const pick = e.target.closest('[data-pick]');
        if (!pick || !pickerTarget) return;
        addAttachment(pickerTarget, parseInt(pick.dataset.form, 10), pick.dataset.pick);
        bootstrap.Offcanvas.getOrCreateInstance(pickerEl).hide();
    });

    /* ---------------------------------------------------------------------- *
     |  Inspector                                                             |
     * ---------------------------------------------------------------------- */

    function renderInspector() {
        const section = selectedSection();

        if (!section) {
            el.inspector.innerHTML = '<div class="rp-ins-head"><span class="rp-ins-title">No section selected</span></div>'
                + '<span class="rp-ins-note">Select a section on the canvas to edit its title, type and visuals.</span>';
            return;
        }

        const index = sectionIndex(section.id);
        const att = section.attachments.find(a => a.id === state.sel.att) || null;
        const handle = att ? mounted.get(att.id) : null;

        let html = '<div class="rp-ins-head"><span class="rp-ins-title">Section ' + (index + 1) + '</span><span class="rp-pill">' + esc(ReportDoc.attachmentLabel(section)) + '</span></div>';

        html += '<div class="rp-ins-group"><span class="rp-kicker">Section title</span>'
            + '<input class="rp-ins-input" type="text" data-ins-title value="' + esc(section.title) + '" placeholder="Section title"></div>';

        html += '<div class="rp-ins-group"><span class="rp-kicker">Section type</span><span class="rp-seg is-grow">'
            + '<button type="button" class="' + (section.type === 'narrative' ? 'is-on' : '') + '" data-type="narrative">Narrative</button>'
            + '<button type="button" class="' + (section.type === 'visual' ? 'is-on' : '') + '" data-type="visual">With charts</button>'
            + '</span></div>';

        html += '<div class="rp-ins-group"><span class="rp-kicker">Attached visuals</span>';
        if (section.type === 'narrative') {
            html += '<span class="rp-ins-note">Narrative only. Switch the type to attach charts from a source form.</span>';
        } else {
            if (!section.attachments.length) html += '<span class="rp-ins-note">No visuals yet. Attach a chart from any question in the source forms.</span>';
            html += section.attachments.map(a => {
                const h = mounted.get(a.id);
                const n = responses(a);
                const kind = h && h.chartType() ? chartLabel(h.chartType()) : (h && h.visualizer() ? visualizerLabel(h.visualizer()) : 'Visual');
                return '<button type="button" class="rp-ins-att' + (a.id === state.sel.att ? ' is-active' : '') + '" data-ins-att="' + esc(a.id) + '">'
                    +   '<span class="rp-ins-att-q">' + esc(questionTitle(a)) + '</span>'
                    +   '<span class="rp-ins-att-meta">' + esc(formTitle(a.form)) + ' · ' + esc(kind) + ' · ' + n + ' responses</span>'
                    + '</button>';
            }).join('');
            html += '<button type="button" class="rp-btn is-dashed" data-ins-attach><i class="fa-solid fa-plus"></i>Attach visualisation</button>';
        }
        html += '</div>';

        if (att && handle) {
            const types = handle.chartTypes();
            const vizs = handle.visualizers();
            html += '<div class="rp-divider"></div><div class="rp-ins-group"><span class="rp-kicker">Selected visual</span>'
                + '<span class="rp-ins-field" title="' + esc(att.question) + '">' + esc(questionTitle(att)) + '</span>'
                + '<span class="rp-ins-field rp-ins-form" title="Source form"><i class="fa-regular fa-table-tree me-1"></i>' + esc(formTitle(att.form)) + '</span>';

            if (vizs.length > 1) {
                html += '<select class="rp-select" data-ins-viz>' + vizs.map(v =>
                    '<option value="' + esc(v) + '"' + (v === handle.visualizer() ? ' selected' : '') + '>' + esc(visualizerLabel(v)) + '</option>'
                ).join('') + '</select>';
            }
            if (types.length > 1) {
                html += '<span class="rp-seg is-grow">' + types.map(t =>
                    '<button type="button" class="' + (t === handle.chartType() ? 'is-on' : '') + '" data-ins-chart="' + esc(t) + '">' + esc(chartLabel(t)) + '</button>'
                ).join('') + '</span>';
            }
            if (handle.supportsSort()) {
                html += '<select class="rp-select" data-ins-sort>' + ReportDoc.SORTS.map(s =>
                    '<option value="' + s + '"' + (s === att.sort ? ' selected' : '') + '>' + esc(ReportDoc.SORT_LABELS[s]) + '</option>'
                ).join('') + '</select>';
            }
            html += '<button type="button" class="rp-btn" data-ins-remove><i class="fa-regular fa-trash-can"></i>Remove visual</button></div>';
        }

        el.inspector.innerHTML = html;
    }

    el.inspector.addEventListener('input', e => {
        if (!e.target.matches('[data-ins-title]')) return;
        const section = selectedSection();
        if (!section) return;

        section.title = e.target.value;
        const cardInput = el.canvas.querySelector('[data-title="' + section.id + '"]');
        if (cardInput) cardInput.value = section.title;

        markDirty();
        renderOutline();
        renderMeta();
        scheduleSave();
    });

    el.inspector.addEventListener('change', e => {
        const t = e.target;
        if (t.matches('[data-ins-viz]') && state.sel.att) setVisualizer(state.sel.att, t.value);
        if (t.matches('[data-ins-sort]') && state.sel.att) {
            const found = attachmentById(state.sel.att);
            const handle = mounted.get(state.sel.att);
            if (!found) return;
            found.att.sort = t.value;
            if (handle) handle.setSort(t.value);
            const sel = el.canvas.querySelector('.rp-att[data-att="' + state.sel.att + '"] [data-sort]');
            if (sel) sel.value = t.value;
            commit('text');
        }
    });

    el.inspector.addEventListener('click', e => {
        const t = e.target;
        const section = selectedSection();
        if (!section) return;

        const type = t.closest('[data-type]');
        if (type) { setSectionType(section.id, type.dataset.type); return; }

        const insAtt = t.closest('[data-ins-att]');
        if (insAtt) {
            select(section.id, insAtt.dataset.insAtt);
            const attEl = el.canvas.querySelector('.rp-att[data-att="' + insAtt.dataset.insAtt + '"]');
            if (attEl) attEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            return;
        }

        if (t.closest('[data-ins-attach]')) { openAttachPicker(section.id); return; }

        const chart = t.closest('[data-ins-chart]');
        if (chart && state.sel.att) { setChartType(state.sel.att, chart.dataset.insChart); return; }

        if (t.closest('[data-ins-remove]') && state.sel.att) removeAttachment(state.sel.att);
    });

    /* ---------------------------------------------------------------------- *
     |  Filters: one builder per source form                                  |
     * ---------------------------------------------------------------------- */

    const filterBuilders = {};
    const filterSwitcher = document.getElementById('reportFilterForms');
    const filterPanels = document.getElementById('reportFilterPanels');
    let activeFilterForm = null;

    /** Build (once) the filter panel + builder for a form and load its saved filters. */
    function filterBuilderFor(formId, saved) {
        if (filterBuilders[formId]) return filterBuilders[formId];
        const ctx = ctxOf(formId);
        if (!ctx) return null;

        const panel = document.createElement('div');
        panel.dataset.formFilters = formId;
        panel.hidden = true;
        filterPanels.appendChild(panel);

        const builder = FilterBuilder.create({
            survey: ctx.survey,
            flags: ctx.flags,
            view: ReportFilterView.create(panel),
            onChange: filters => renderFilterCount(formId, filters),
        });
        builder.load(saved || {});
        filterBuilders[formId] = { builder, panel };
        return filterBuilders[formId];
    }

    /** The form selector's option label: title plus how many filters the form carries. */
    function renderFilterCount(formId, filters) {
        const opt = filterSwitcher.querySelector('option[value="' + formId + '"]');
        if (!opt) return;
        const n = FilterBuilder.countActive(filters);
        opt.textContent = formTitle(formId) + (n ? ' (' + n + ' filter' + (n === 1 ? '' : 's') + ')' : '');
    }

    function destroyFilterBuilder(formId) {
        const entry = filterBuilders[formId];
        if (!entry) return;
        entry.builder.destroy();
        entry.panel.remove();
        delete filterBuilders[formId];
        if (activeFilterForm === formId) activeFilterForm = null;
    }

    function showFilterForm(formId) {
        activeFilterForm = formId;
        Object.keys(filterBuilders).forEach(id => { filterBuilders[id].panel.hidden = String(id) !== String(formId); });
        filterSwitcher.value = String(formId);
    }

    function renderFilterSwitcher() {
        filterSwitcher.innerHTML = order.map(id => '<option value="' + id + '">' + esc(formTitle(id)) + '</option>').join('');
        filterSwitcher.hidden = order.length < 2;
        order.forEach(id => { if (filterBuilders[id]) renderFilterCount(id, filterBuilders[id].builder.compileFilters()); });

        if (!order.length) return;
        showFilterForm(order.indexOf(activeFilterForm) !== -1 ? activeFilterForm : order[0]);
    }

    filterSwitcher.addEventListener('change', () => showFilterForm(parseInt(filterSwitcher.value, 10)));

    /** { formId: filters } for every attached form. */
    function compileAllFilters() {
        const all = {};
        order.forEach(id => {
            const entry = filterBuilders[id];
            const compiled = entry ? entry.builder.compileFilters() : (cfg.filters && cfg.filters[id]) || {};
            if (compiled && Object.keys(compiled).length) all[id] = compiled;
        });
        return all;
    }

    document.getElementById('reportApplyFilters').addEventListener('click', function () {
        const filters = compileAllFilters();
        buttonState('#reportApplyFilters', 'loading');

        fetch(cfg.routes.data, {
            method: 'POST',
            headers: HEADERS(),
            body: JSON.stringify({ filters }),
        })
            .then(r => r.json())
            .then(res => {
                if (!res.status) throw new Error(res.message || 'Could not load submissions');

                const sets = res.collections || {};
                let total = 0;
                order.forEach(id => {
                    if (!ctxs[id]) return;
                    ctxs[id].data = Array.isArray(sets[id]) ? sets[id] : [];
                    total += ctxs[id].data.length;
                });

                mounted.forEach((h, attId) => {
                    const found = attachmentById(attId);
                    if (found && ctxs[found.att.form]) h.updateData(ctxs[found.att.form].data);
                });
                el.canvas.querySelectorAll('.rp-att').forEach(attEl => {
                    const found = attachmentById(attEl.dataset.att);
                    if (!found) return;
                    const n = responses(found.att);
                    attEl.querySelector('.rp-att-n').textContent = n + ' response' + (n === 1 ? '' : 's');
                });

                commit('text');
                refreshQueries();
                toast.success({ message: total + ' submission' + (total === 1 ? '' : 's') + ' match the filters' });
                bootstrap.Offcanvas.getOrCreateInstance(document.getElementById('reportFiltersOffcanvas')).hide();
            })
            .catch(err => toast.error({ message: err.message || 'Could not apply filters' }))
            .finally(() => buttonState('#reportApplyFilters', 'reset', '<i class="fa-solid fa-check me-1"></i>Apply filters'));
    });

    /* ---------------------------------------------------------------------- *
     |  Queries (SurveyQL): sources, expressions, inline answers              |
     * ---------------------------------------------------------------------- */

    const qel = {
        list:        document.getElementById('reportQueryList'),
        sources:     document.getElementById('reportQuerySources'),
        expressions: document.getElementById('reportQueryExpressions'),
        tryInput:    document.getElementById('reportQueryTry'),
        tryResult:   document.getElementById('reportQueryTryResult'),
        panel:       document.getElementById('reportQueriesOffcanvas'),
    };

    let queryTimer = null, queryRun = 0, queriesRunning = false;

    /** Re-evaluate every query (debounced); answers from a stale run are dropped. */
    function refreshQueries(delay) {
        clearTimeout(queryTimer);
        queriesRunning = true;
        queryTimer = setTimeout(() => {
            const run = ++queryRun;
            const tryQuery = qel.tryInput.value.trim();
            ReportQuery.evaluate(doc, svql, ctxs, tryQuery ? [tryQuery] : []).then(results => {
                if (run !== queryRun) return;
                queriesRunning = false;
                queryResults = results;
                renderQueryResults();
            });
        }, delay === undefined ? 400 : delay);
    }

    const resultFor = (kind, key) => (queryResults && queryResults[kind] ? queryResults[kind][key] : null);

    /** One answer as a chip: the value, or why there is none. */
    function answerHtml(answer, pending) {
        if (!ReportQuery.available()) return '<span class="rp-q is-error">SurveyQL not loaded</span>';
        if (!answer) return pending ? '<span class="rp-q is-pending">…</span>' : '';
        return answer.ok
            ? '<span class="rp-q">' + esc(answer.text) + '</span>'
            : '<span class="rp-q is-error" title="' + esc(answer.error) + '"><i class="fa-solid fa-triangle-exclamation"></i> ' + esc(answer.error) + '</span>';
    }

    /** The tokens in a section's text with their answers, shown under its textarea. */
    function queryHintsHtml(section) {
        const queries = [];
        section.body.forEach(p => ReportQuery.queriesIn(p).forEach(q => { if (queries.indexOf(q) === -1) queries.push(q); }));
        return queries.map(q =>
            '<span class="rp-q-hint"><code>' + esc(q || '(empty)') + '</code>' + answerHtml(resultFor('tokens', q), queriesRunning) + '</span>'
        ).join('');
    }

    function renderQueryHints(sectionId) {
        const section = sectionById(sectionId);
        const box = el.canvas.querySelector('[data-q-hints="' + sectionId + '"]');
        if (section && box) box.innerHTML = queryHintsHtml(section);
    }

    /** The value side of a rail row: the answer, a warning icon, or a pending mark. */
    function railValueHtml(answer) {
        if (!ReportQuery.available()) return '<span class="rp-qlist-val is-error" title="SurveyQL not loaded"><i class="fa-solid fa-triangle-exclamation"></i></span>';
        if (!answer) return '<span class="rp-qlist-val is-muted">' + (queriesRunning ? '…' : '—') + '</span>';
        return answer.ok
            ? '<span class="rp-qlist-val" title="' + esc(answer.text) + '">' + esc(answer.text) + '</span>'
            : '<span class="rp-qlist-val is-error" title="' + esc(answer.error) + '"><i class="fa-solid fa-triangle-exclamation"></i></span>';
    }

    /** Rail summary: one `name … value` row per expression; any click opens the panel. */
    function renderQueryList() {
        if (!svql.expressions.length) {
            qel.list.innerHTML = '<button type="button" class="rp-source-add" data-q-open><i class="fa-solid fa-plus"></i>'
                + (svql.sources.length ? 'Add expression' : 'Set up queries') + '</button>';
            return;
        }
        qel.list.innerHTML = svql.expressions.map(e =>
            '<button type="button" class="rp-qlist-item" data-q-open title="' + esc(e.svql) + '">'
            +   '<span class="rp-qlist-name">' + esc(e.name || '(unnamed)') + '</span>'
            +   railValueHtml(resultFor('expressions', e.name))
            + '</button>'
        ).join('');
    }

    document.getElementById('reportQueryGroup').addEventListener('click', e => {
        if (e.target.closest('[data-q-open]')) bootstrap.Offcanvas.getOrCreateInstance(qel.panel).show();
    });

    /* panel rows are rebuilt only on add/remove, so typing never loses focus */

    function formOptions(selected) {
        return order.map(id => '<option value="' + id + '"' + (id === selected ? ' selected' : '') + '>' + esc(formTitle(id)) + '</option>').join('');
    }

    function renderQuerySources() {
        qel.sources.innerHTML = svql.sources.map((s, i) =>
            '<div class="rp-qrow" data-q-source="' + i + '">'
            +   '<input type="text" class="form-control form-control-sm font-monospace rp-qrow-name" data-q-field="name" value="' + esc(s.name) + '" placeholder="name" spellcheck="false">'
            +   '<select class="form-select form-select-sm" data-q-field="form">'
            +     (!s.form || order.indexOf(s.form) === -1 ? '<option value="" selected>Pick a form</option>' : '')
            +     formOptions(s.form)
            +   '</select>'
            +   '<button type="button" class="btn btn-sm btn-link text-danger p-0" data-q-remove title="Remove source"><i class="fa-solid fa-xmark"></i></button>'
            + '</div>'
        ).join('') || '<span class="small text-body-tertiary">No sources yet.</span>';
    }

    function renderQueryExpressions() {
        qel.expressions.innerHTML = svql.expressions.map((e, i) =>
            '<div class="rp-qrow" data-q-expression="' + i + '">'
            +   '<input type="text" class="form-control form-control-sm font-monospace rp-qrow-name" data-q-field="name" value="' + esc(e.name) + '" placeholder="name" spellcheck="false">'
            +   '<input type="text" class="form-control form-control-sm font-monospace" data-q-field="svql" value="' + esc(e.svql) + '" placeholder="source | filter age >= 18" spellcheck="false">'
            +   '<button type="button" class="btn btn-sm btn-link text-danger p-0" data-q-remove title="Remove expression"><i class="fa-solid fa-xmark"></i></button>'
            +   '<span class="rp-qrow-result" data-q-result>' + answerHtml(resultFor('expressions', e.name), queriesRunning) + '</span>'
            + '</div>'
        ).join('');
    }

    /** Refresh every place an answer shows, without touching any input. */
    function renderQueryResults() {
        qel.expressions.querySelectorAll('[data-q-expression]').forEach(row => {
            const e = svql.expressions[row.dataset.qExpression];
            if (e) row.querySelector('[data-q-result]').innerHTML = answerHtml(resultFor('expressions', e.name), false);
        });

        const tryQuery = qel.tryInput.value.trim();
        qel.tryResult.innerHTML = tryQuery ? answerHtml(resultFor('tokens', tryQuery), false) : '';

        doc.sections.forEach(s => renderQueryHints(s.id));
        renderQueryList();
        if (state.preview) ReportRenderer.fillQueries(el.preview, doc, { queries: queryResults, queryErrors: true });
    }

    /** Query settings changed: saved with the draft, then re-run. */
    function queriesChanged() {
        markDirty();
        renderMeta();
        renderSources();
        scheduleSave();
        refreshQueries();
    }

    function onQueryInput(e) {
        const field = e.target.dataset.qField;
        if (!field) return;
        const sRow = e.target.closest('[data-q-source]');
        const eRow = e.target.closest('[data-q-expression]');
        const item = sRow ? svql.sources[sRow.dataset.qSource] : eRow ? svql.expressions[eRow.dataset.qExpression] : null;
        if (!item) return;

        item[field] = field === 'form' ? (parseInt(e.target.value, 10) || null) : e.target.value.trim();
        queriesChanged();
    }

    qel.panel.addEventListener('input', e => { if (e.target.matches('input[data-q-field]')) onQueryInput(e); });
    qel.panel.addEventListener('change', e => { if (e.target.matches('select[data-q-field]')) onQueryInput(e); });

    const takenNames = () => svql.sources.map(s => s.name).concat(svql.expressions.map(x => x.name));

    qel.panel.addEventListener('click', e => {
        if (e.target.closest('[data-q-add-source]')) {
            if (!order.length) { toast.error({ message: 'Add a source form first' }); return; }
            const form = order.find(id => queriedForms().indexOf(id) === -1) || order[0];
            svql.sources.push({ name: ReportQuery.suggestName(ctxs[form], takenNames()), form });
            renderQuerySources();
            queriesChanged();
            return;
        }

        if (e.target.closest('[data-q-add-expression]')) {
            const taken = takenNames();
            let n = svql.expressions.length + 1, name = 'q' + n;
            while (taken.indexOf(name) !== -1) name = 'q' + ++n;
            svql.expressions.push({ name, svql: (svql.sources[0] ? svql.sources[0].name : 'source') + ' | count(all)' });
            renderQueryExpressions();
            queriesChanged();
            const input = qel.expressions.querySelector('[data-q-expression="' + (svql.expressions.length - 1) + '"] [data-q-field="svql"]');
            if (input) input.focus();
            return;
        }

        const remove = e.target.closest('[data-q-remove]');
        if (!remove) return;
        const sRow = remove.closest('[data-q-source]');
        const eRow = remove.closest('[data-q-expression]');
        if (sRow) { svql.sources.splice(parseInt(sRow.dataset.qSource, 10), 1); renderQuerySources(); }
        if (eRow) { svql.expressions.splice(parseInt(eRow.dataset.qExpression, 10), 1); renderQueryExpressions(); }
        queriesChanged();
    });

    qel.tryInput.addEventListener('input', () => refreshQueries());

    /* ---------------------------------------------------------------------- *
     |  Top bar tooltips                                                      |
     * ---------------------------------------------------------------------- */

    /** Icon buttons carry their name in `title`; Bootstrap shows it below the button. */
    function initTips() {
        document.querySelectorAll('.rp-topbar .rp-btn.is-icon[title]').forEach(btn => {
            if (!btn.hasAttribute('aria-label')) btn.setAttribute('aria-label', btn.title);
            bootstrap.Tooltip.getOrCreateInstance(btn, { placement: 'bottom', trigger: 'hover' });
            // the button opens panels and swaps modes: don't leave its tip hanging
            btn.addEventListener('click', () => bootstrap.Tooltip.getInstance(btn)?.hide());
        });
    }

    function setTip(btn, text) {
        btn.setAttribute('aria-label', text);
        const tip = bootstrap.Tooltip.getInstance(btn);
        if (tip) tip.setContent({ '.tooltip-inner': text });
        else btn.title = text;
    }

    /* ---------------------------------------------------------------------- *
     |  Boot                                                                  |
     * ---------------------------------------------------------------------- */

    function boot() {
        pane('rail', global.innerWidth >= 992);   // outline open on large screens
        pane('inspector', false);                 // shows once a section is focused
        initTips();

        order.forEach(id => filterBuilderFor(id, (cfg.filters && cfg.filters[id]) || {}));
        renderFilterSwitcher();

        renderQuerySources();
        renderQueryExpressions();
        renderQueryList();
        refreshQueries(0);

        renderSources();
        renderPicker();
        renderStatusPill();
        renderCanvas();
        renderOutline();
        renderInspector();
        renderMeta();
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
    else boot();

})(window);
