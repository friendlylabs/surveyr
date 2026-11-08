/**
 * Submissions table
 * --------------------------------------------------------------------------
 * Grid.js over every submission the server sent (already filtered and newest
 * first). Columns: a sticky select + response id, the submission time, one
 * column per question, and, only when the form has a flag group, a flag pill.
 *
 * Selection and flags live in `selected` / `flagById` rather than in Grid.js'
 * rows, so formatters read the current value whenever a page is re-rendered.
 */
const flagGroup = @json($flags ?? null);          // [{key, label, color}] or null
const surveyJson = @json($form->content) || {};
const surveyResults = @json($submissions) || [];

const survey = new Survey.Model(surveyJson);
const questions = survey.getAllQuestions();

const flagByKey = new Map((flagGroup || []).map(f => [f.key, f]));
const flagById = new Map(surveyResults.map(r => [r.id, flagByKey.has(r.flag) ? r.flag : null]));
const selected = new Set();

/* ---------------------------------------------------------------------- *
 |  Formatting                                                            |
 * ---------------------------------------------------------------------- */

function esc(s) {
    return String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

const responseId = id => `RSP-${id}`;

function relativeTime(iso) {
    if (!iso) return '';
    const date = new Date(iso);
    const mins = Math.floor((Date.now() - date) / 60000);
    if (mins < 1) return 'just now';
    if (mins < 60) return `${mins}m ago`;
    if (mins < 60 * 24) return `${Math.floor(mins / 60)}h ago`;

    const startOfDay = d => new Date(d.getFullYear(), d.getMonth(), d.getDate());
    const days = Math.round((startOfDay(new Date()) - startOfDay(date)) / 86400000);
    if (days <= 1) return 'yesterday';
    if (days < 30) return `${days}d ago`;
    if (days < 365) return `${Math.floor(days / 30)}mo ago`;
    return `${Math.floor(days / 365)}y ago`;
}

function submittedAt(iso) {
    if (!iso) return '';
    const d = new Date(iso);
    const opts = { month: 'short', day: 'numeric' };
    if (d.getFullYear() !== new Date().getFullYear()) opts.year = 'numeric';
    const time = d.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit', hour12: false });
    return `${d.toLocaleDateString(undefined, opts)}, ${time}`;
}

/** An answer as plain text: files become a count, lists and objects are joined. */
function processValue(value) {
    if (Array.isArray(value)) {
        if (value.length && value[0] && typeof value[0] === 'object' && 'name' in value[0] && 'type' in value[0]) {
            return `${value.length} attachment${value.length > 1 ? 's' : ''}`;
        }
        return value.map(processValue).join(', ');
    }
    if (value && typeof value === 'object') return Object.values(value).map(processValue).join(', ');
    return value != null ? String(value) : '';
}

/** How a question's cells read: 'rating' (n/max), 'number', 'money' or plain text. */
function questionKind(q, values) {
    const type = q.getType ? q.getType() : '';
    if (type === 'rating') return 'rating';
    if (type === 'expression' || (type === 'text' && ['number', 'range'].includes(q.inputType))) return 'number';

    const filled = values.filter(v => v !== '');
    if (filled.length && filled.filter(v => /[$€£¥₹]/.test(v)).length / filled.length >= 0.5) return 'money';
    return 'text';
}

function ratingMax(q) {
    if (Array.isArray(q.rateValues) && q.rateValues.length) return q.rateValues.length;
    return q.rateMax || 5;
}

function flagPill(id) {
    const flag = flagByKey.get(flagById.get(id));
    if (!flag) {
        return `<button type="button" class="subs-flag is-empty" data-flag-for="${id}">Set flag <i class="fa-solid fa-chevron-right"></i></button>`;
    }
    return `<button type="button" class="subs-flag" data-flag-for="${id}" style="--flag: ${esc(flag.color)}"><i class="subs-flag-dot"></i>${esc(flag.label)}</button>`;
}

/* ---------------------------------------------------------------------- *
 |  Columns and rows                                                      |
 * ---------------------------------------------------------------------- */

const answers = questions.map(q => surveyResults.map(r => processValue(r[q.name])));
const kinds = questions.map((q, i) => questionKind(q, answers[i]));

const columns = [
    { id: 'subs-id', name: 'ID', hidden: true },
    {
        id: 'subs-response',
        name: gridjs.html('<div class="subs-select"><input type="checkbox" class="form-check-input" data-select-all aria-label="Select all on this page"><span>Response</span></div>'),
        sort: false,
        formatter: (cell, row) => {
            const id = row.cells[0].data;
            return gridjs.html(`
                <div class="subs-select">
                    <input type="checkbox" class="form-check-input" data-select="${id}" ${selected.has(id) ? 'checked' : ''} aria-label="Select ${responseId(id)}">
                    <div>
                        <div class="subs-id">${responseId(id)}</div>
                        <div class="subs-ago">${esc(relativeTime(cell))}</div>
                    </div>
                </div>`);
        },
    },
    {
        id: 'subs-submitted',
        name: 'Submitted',
        formatter: cell => gridjs.html(`<span class="subs-mono">${esc(submittedAt(cell))}</span>`),
    },
    ...questions.map((q, i) => ({
        id: `q-${i}`,
        name: q.title || slugToWords(q.name),
        attributes: { 'data-kind': kinds[i] },
        formatter: cell => {
            if (cell === '' || cell == null) return gridjs.html('<span class="subs-empty">—</span>');
            if (kinds[i] === 'rating') return gridjs.html(`<span class="subs-mono subs-strong">${esc(cell)}/${ratingMax(q)}</span>`);
            if (kinds[i] === 'money') return gridjs.html(`<span class="subs-mono subs-strong">${esc(cell)}</span>`);
            if (kinds[i] === 'number') return gridjs.html(`<span class="subs-mono">${esc(cell)}</span>`);
            return cell;
        },
    })),
];

if (flagGroup) {
    columns.push({
        id: 'subs-flag',
        name: 'Flag',
        sort: false,
        formatter: (cell, row) => gridjs.html(flagPill(row.cells[0].data)),
    });
}

const rows = surveyResults.map((r, idx) => [
    r.id,
    r.created_at || '',
    r.created_at || '',
    ...answers.map(list => list[idx]),
    ...(flagGroup ? [''] : []),
]);

/* ---------------------------------------------------------------------- *
 |  Grid                                                                  |
 * ---------------------------------------------------------------------- */

const tableHost = document.getElementById('surveyDataTable');
let grid = null;

document.addEventListener('DOMContentLoaded', function () {
    if (!rows.length) return;

    grid = new gridjs.Grid({
        columns,
        data: rows,
        search: true,
        sort: true,
        resizable: true,
        autoWidth: false,       // columns size to their content; the wrapper scrolls sideways
        pagination: { limit: 25, summary: true },
        className: { container: 'subs-grid' },
    });

    grid.render(tableHost);

    // a click anywhere on a row but its controls opens the submission
    grid.on('rowClick', (event, row) => {
        if (event.target.closest('input, label, button, a, .gridjs-resizable')) return;
        viewSubmissionCanvas(row.cells[0].data);
    });

    // Grid.js re-renders the body on every page / sort / search
    new MutationObserver(syncSelectAll).observe(tableHost, { childList: true, subtree: true });
});

/* ---------------------------------------------------------------------- *
 |  Selection + bulk bar                                                  |
 * ---------------------------------------------------------------------- */

const bulkBar = document.getElementById('subsBulkBar');

function pageIds() {
    return Array.from(tableHost.querySelectorAll('[data-select]')).map(i => Number(i.dataset.select));
}

function syncSelectAll() {
    const all = tableHost.querySelector('[data-select-all]');
    if (!all) return;
    const ids = pageIds();
    const picked = ids.filter(id => selected.has(id)).length;
    all.checked = ids.length > 0 && picked === ids.length;
    all.indeterminate = picked > 0 && picked < ids.length;

    tableHost.querySelectorAll('[data-select]').forEach(input => {
        input.closest('tr')?.classList.toggle('is-selected', input.checked);
    });
}

function renderBulkBar() {
    if (!bulkBar) return;
    bulkBar.hidden = selected.size === 0;
    bulkBar.querySelector('[data-bulk-count]').textContent = `${selected.size} selected`;
}

tableHost?.addEventListener('change', e => {
    const input = e.target;
    if (input.matches('[data-select-all]')) {
        pageIds().forEach(id => input.checked ? selected.add(id) : selected.delete(id));
        tableHost.querySelectorAll('[data-select]').forEach(i => { i.checked = input.checked; });
    } else if (input.matches('[data-select]')) {
        const id = Number(input.dataset.select);
        input.checked ? selected.add(id) : selected.delete(id);
    } else {
        return;
    }
    syncSelectAll();
    renderBulkBar();
});

function clearSelection() {
    selected.clear();
    tableHost.querySelectorAll('[data-select], [data-select-all]').forEach(i => { i.checked = false; i.indeterminate = false; });
    syncSelectAll();
    renderBulkBar();
}

/* ---------------------------------------------------------------------- *
 |  Flag menu (one floating menu, shared by every pill and the bulk bar)  |
 * ---------------------------------------------------------------------- */

const flagMenu = document.createElement('div');
flagMenu.className = 'subs-flag-menu';
flagMenu.hidden = true;
document.body.appendChild(flagMenu);
let flagTarget = null;   // a submission id, or 'bulk'

function openFlagMenu(anchor, target) {
    flagTarget = target;
    const current = target === 'bulk' ? null : flagById.get(target);

    flagMenu.innerHTML = (flagGroup || []).map(f => `
            <button type="button" data-pick="${esc(f.key)}" class="${f.key === current ? 'is-current' : ''}">
                <i class="subs-flag-dot" style="--flag: ${esc(f.color)}"></i>${esc(f.label)}
                ${f.key === current ? '<i class="fa-solid fa-check ms-auto"></i>' : ''}
            </button>`).join('')
        + `<hr><button type="button" data-pick="" class="is-muted"><i class="fa-solid fa-xmark"></i>Clear flag</button>`;

    flagMenu.hidden = false;
    const box = anchor.getBoundingClientRect();
    const menu = flagMenu.getBoundingClientRect();
    const left = Math.min(box.right - menu.width, window.innerWidth - menu.width - 8);
    const top = box.bottom + menu.height + 6 > window.innerHeight ? box.top - menu.height - 6 : box.bottom + 6;
    flagMenu.style.left = `${Math.max(8, left)}px`;
    flagMenu.style.top = `${Math.max(8, top)}px`;
}

function closeFlagMenu() {
    flagMenu.hidden = true;
    flagTarget = null;
}

document.addEventListener('click', e => {
    const pill = e.target.closest('[data-flag-for]');
    if (pill) {
        e.stopPropagation();
        const id = Number(pill.dataset.flagFor);
        flagTarget === id && !flagMenu.hidden ? closeFlagMenu() : openFlagMenu(pill, id);
        return;
    }

    const bulk = e.target.closest('[data-bulk-flag]');
    if (bulk) {
        flagTarget === 'bulk' && !flagMenu.hidden ? closeFlagMenu() : openFlagMenu(bulk, 'bulk');
        return;
    }

    const pick = e.target.closest('[data-pick]');
    if (pick && flagMenu.contains(pick)) {
        const target = flagTarget;
        closeFlagMenu();
        target === 'bulk' ? saveBulkFlag(pick.dataset.pick) : saveFlag(target, pick.dataset.pick);
        return;
    }

    if (!flagMenu.contains(e.target)) closeFlagMenu();
});

document.addEventListener('keydown', e => { if (e.key === 'Escape') closeFlagMenu(); });
window.addEventListener('resize', closeFlagMenu);
document.addEventListener('scroll', closeFlagMenu, true);

function repaintFlags(ids) {
    ids.forEach(id => {
        const pill = tableHost.querySelector(`[data-flag-for="${id}"]`);
        if (pill) pill.outerHTML = flagPill(id);
    });
}

function postFlag(url, data) {
    return $.ajax({ url, method: 'POST', data: { _token: `{{ csrf_token() }}`, ...data } })
        .then(response => {
            if (!response.status) throw new Error(response.message || 'An error occurred');
            return response;
        })
        .catch(error => {
            toast.error({ message: error.responseJSON?.message || error.message || 'An error occurred' });
            throw error;
        });
}

function saveFlag(id, key) {
    const previous = flagById.get(id);
    flagById.set(id, key || null);
    repaintFlags([id]);

    postFlag(`@route('collections.flag', ':id')`.replace(':id', id), { flag: key })
        .then(response => toast.success({ message: response.message }))
        .catch(() => { flagById.set(id, previous); repaintFlags([id]); });
}

function saveBulkFlag(key) {
    const ids = Array.from(selected);
    if (!ids.length) return;

    postFlag(`@route('collections.flagBulk', $form->id)`, { flag: key, ids })
        .then(response => {
            ids.forEach(id => flagById.set(id, key || null));
            repaintFlags(ids);
            toast.success({ message: response.message });
            clearSelection();
        })
        .catch(() => {});
}

/* ---------------------------------------------------------------------- *
 |  Export                                                                |
 * ---------------------------------------------------------------------- */

function exportRows(onlySelected) {
    const header = ['Response', 'Submitted', ...questions.map(q => q.title || slugToWords(q.name)), ...(flagGroup ? ['Flag'] : [])];
    const body = surveyResults
        .map((r, idx) => ({ r, idx }))
        .filter(({ r }) => !onlySelected || selected.has(r.id))
        .map(({ r, idx }) => [
            responseId(r.id),
            submittedAt(r.created_at),
            ...answers.map(list => list[idx]),
            ...(flagGroup ? [flagByKey.get(flagById.get(r.id))?.label || ''] : []),
        ]);

    const worksheet = XLSX.utils.aoa_to_sheet([header, ...body]);
    const workbook = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(workbook, worksheet, 'Survey Results');
    XLSX.writeFile(workbook, `${("{{ $form->title }}").toLowerCase()}_${new Date().toLocaleDateString()}.xlsx`);
}

document.getElementById('exportExcel')?.addEventListener('click', () => exportRows(false));
document.querySelector('[data-bulk-export]')?.addEventListener('click', () => exportRows(true));
document.querySelector('[data-bulk-clear]')?.addEventListener('click', clearSelection);

/**
 * Attachments ZIP. Selected rows when asked for, otherwise whatever the page's
 * filter / saved view shows. Errors come back as JSON, the archive as a blob.
 */
async function exportAttachments(onlySelected, button) {
    const body = new FormData();
    body.append('_token', `{{ csrf_token() }}`);

    if (onlySelected) {
        selected.forEach(id => body.append('ids[]', id));
    } else {
        const params = new URLSearchParams(window.location.search);
        ['filters', 'view'].forEach(key => params.has(key) && body.append(key, params.get(key)));
    }

    const original = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>' + (onlySelected ? ' Preparing…' : '');

    try {
        const response = await fetch(`@route('collections.attachments', $form->id)`, { method: 'POST', body });
        const type = response.headers.get('Content-Type') || '';

        if (!type.includes('application/zip')) {
            const payload = await response.json().catch(() => ({}));
            throw new Error(payload.message || 'Failed to export attachments');
        }

        const disposition = response.headers.get('Content-Disposition') || '';
        const filename = (disposition.match(/filename="([^"]+)"/) || [])[1] || 'attachments.zip';
        const url = URL.createObjectURL(await response.blob());

        const link = Object.assign(document.createElement('a'), { href: url, download: filename });
        document.body.appendChild(link);
        link.click();
        link.remove();
        setTimeout(() => URL.revokeObjectURL(url), 1000);

        const count = response.headers.get('X-Attachment-Count');
        toast.success({ message: count ? `Exported ${count} attachment${count === '1' ? '' : 's'}` : 'Attachments exported' });
    } catch (error) {
        toast.error({ message: error.message });
    } finally {
        button.disabled = false;
        button.innerHTML = original;
    }
}

document.getElementById('exportAttachments')?.addEventListener('click', e => exportAttachments(false, e.currentTarget));
document.querySelector('[data-bulk-attachments]')?.addEventListener('click', e => exportAttachments(true, e.currentTarget));

/* ---------------------------------------------------------------------- *
 |  Submission preview                                                    |
 * ---------------------------------------------------------------------- */

const surveyTheme = () => (localStorage.getItem('phoenixTheme') ?? 'light') === 'dark'
    ? SurveyTheme.SolidDarkPanelless
    : SurveyTheme.SolidLightPanelless;

async function viewSubmissionCanvas(submissionId) {
    toast.info({ message: 'Loading submission data...' });

    const response = await fetch(`@route('collections.show', ':id')`.replace(':id', submissionId));
    if (!response.ok) return toast.error({ message: 'Failed to fetch submission data.' });

    const data = await response.json();
    if (!data.status) return toast.error({ message: data.message || 'An error occurred while fetching submission data.' });

    showSingleCollection(data.submission);
    document.getElementById('surveySubmissionOffcanvasLabel').textContent = responseId(submissionId);
    bootstrap.Offcanvas.getOrCreateInstance(document.getElementById('surveySubmissionOffcanvas')).show();
}

function showSingleCollection(surveyResponse) {
    survey.data = surveyResponse || {};
    survey.mode = 'display';
    survey.currentPageNo = 0;

    survey.render(document.getElementById('surveySubmissionBlock'));
    survey.applyTheme(surveyTheme());
}

// follow the app's theme switch (it writes phoenixTheme to localStorage)
(function () {
    const originalSetItem = localStorage.setItem;
    localStorage.setItem = function (key) {
        originalSetItem.apply(this, arguments);
        if (key === 'phoenixTheme') survey.applyTheme(surveyTheme());
    };
})();
