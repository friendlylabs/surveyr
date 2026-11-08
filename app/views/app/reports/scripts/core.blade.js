/**
 * Report document helpers
 * --------------------------------------------------------------------------
 * Browser-side twin of App\Services\ReportSchema: coerces whatever the server
 * hands over into a v2 document, mints ids, seeds attachments from the form's
 * visualization rules and decides how a section lays out its text and charts.
 *
 * Shared by the editor (edit.blade.js) and the viewer (render.blade.js).
 */
window.ReportDoc = (function (global) {
    'use strict';

    const VERSION = 2;
    const TYPES = ['narrative', 'visual'];
    const SORTS = ['default', 'desc', 'asc'];

    const SORT_LABELS = {
        default: 'Default order',
        desc:    'Highest first',
        asc:     'Lowest first',
    };

    /* ---------------------------------------------------------------------- *
     |  Ids and text                                                          |
     * ---------------------------------------------------------------------- */

    function newId(prefix) {
        return prefix + '_' + Math.random().toString(36).slice(2, 8);
    }

    /** Textarea contents -> paragraphs (split on blank lines or newlines). */
    function textToParagraphs(text) {
        return String(text || '').split(/\n+/).map(p => p.trim()).filter(Boolean);
    }

    /** Legacy descriptions may carry CKEditor markup and entities. */
    function legacyParagraphs(text) {
        const box = document.createElement('div');
        box.innerHTML = String(text || '').replace(/<br\s*\/?>|<\/p>/gi, '\n');
        return textToParagraphs((box.textContent || '').replace(/\u00A0/g, ' '));
    }

    /** Paragraphs -> textarea contents. */
    function paragraphsToText(body) {
        return (Array.isArray(body) ? body : []).join('\n\n');
    }

    function escapeHtml(text) {
        return String(text == null ? '' : text)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    /* ---------------------------------------------------------------------- *
     |  Normalization                                                         |
     * ---------------------------------------------------------------------- */

    function isLegacy(raw) {
        return !!raw && typeof raw === 'object' && !Array.isArray(raw)
            && Array.isArray(raw.sections) && raw.version !== VERSION;
    }

    function fromLegacy(raw, defaultFormId) {
        return {
            version: VERSION,
            sections: raw.sections.filter(s => s && typeof s === 'object').map(section => {
                const attachments = (section.attachments || [])
                    .map(a => (a && typeof a === 'object') ? { question: a.question, form: a.form } : { question: a, form: null })
                    .filter(a => typeof a.question === 'string' && a.question)
                    .map(a => ({ id: newId('att'), form: parseInt(a.form || defaultFormId, 10) || null, question: a.question, visualizer: null, chart_type: null, sort: 'default' }));

                return {
                    id:          section.id || newId('sec'),
                    type:        attachments.length ? 'visual' : 'narrative',
                    title:       String(section.title || '').trim(),
                    body:        Array.isArray(section.body) ? section.body : legacyParagraphs(section.description),
                    attachments: attachments,
                };
            }),
        };
    }

    function normalizeAttachment(raw, defaultFormId) {
        if (!raw || typeof raw !== 'object' || typeof raw.question !== 'string' || !raw.question) return null;

        // every visual belongs to a source form; without one it cannot be drawn
        const form = parseInt(raw.form || defaultFormId, 10);
        if (!form) return null;

        return {
            id:         typeof raw.id === 'string' && raw.id ? raw.id : newId('att'),
            form:       form,
            question:   raw.question,
            visualizer: typeof raw.visualizer === 'string' && raw.visualizer ? raw.visualizer : null,
            chart_type: typeof raw.chart_type === 'string' && raw.chart_type ? raw.chart_type : null,
            sort:       SORTS.indexOf(raw.sort) !== -1 ? raw.sort : 'default',
        };
    }

    /**
     * Anything (null, [], v1, v2) -> a complete v2 document. `defaultFormId`
     * stamps attachments that predate multi-form reports.
     */
    function normalize(raw, defaultFormId) {
        if (!raw || typeof raw !== 'object' || Array.isArray(raw) || !Array.isArray(raw.sections)) {
            return { version: VERSION, sections: [] };
        }

        if (isLegacy(raw)) raw = fromLegacy(raw, defaultFormId);

        const seen = {};
        const sections = raw.sections.filter(s => s && typeof s === 'object').map(section => {
            let id = typeof section.id === 'string' && section.id ? section.id : newId('sec');
            while (seen[id]) id = newId('sec');
            seen[id] = true;

            const attachments = (Array.isArray(section.attachments) ? section.attachments : [])
                .map(a => normalizeAttachment(a, defaultFormId)).filter(Boolean);

            const type = TYPES.indexOf(section.type) !== -1
                ? section.type
                : (attachments.length ? 'visual' : 'narrative');

            const body = typeof section.body === 'string' ? textToParagraphs(section.body) : section.body;

            return {
                id,
                type,
                title:       String(section.title || '').trim(),
                body:        (Array.isArray(body) ? body : []).map(p => String(p).trim()).filter(Boolean),
                attachments: type === 'narrative' ? [] : attachments,
            };
        });

        return { version: VERSION, sections };
    }

    /* ---------------------------------------------------------------------- *
     |  Factories                                                             |
     * ---------------------------------------------------------------------- */

    function newSection(type) {
        return {
            id:          newId('sec'),
            type:        TYPES.indexOf(type) !== -1 ? type : 'narrative',
            title:       '',
            body:        [],
            attachments: [],
        };
    }

    /** Map a viz rule's sort vocabulary onto the attachment's. */
    function sortFromRule(rule) {
        if (!rule) return 'default';
        if (rule.sort === 'value_desc') return 'desc';
        if (rule.sort === 'value_asc')  return 'asc';
        return 'default';
    }

    /** A fresh attachment for a question of a form, seeded from that form's viz rule. */
    function newAttachment(formId, questionName, rule) {
        return {
            id:         newId('att'),
            form:       parseInt(formId, 10),
            question:   questionName,
            visualizer: (rule && rule.visualizer) || null,
            chart_type: (rule && rule.chart_type) || null,
            sort:       sortFromRule(rule),
        };
    }

    /**
     * The attachment with its nulls filled from its form's viz rule, plus the
     * full rule object the bound visualizer needs. Does not mutate the document.
     *
     * @param {object} ctxs  { [formId]: { rules, … } }
     */
    function resolveAttachment(attachment, ctxs) {
        const ctx = ctxs && ctxs[attachment.form];
        const rule = Object.assign({}, (ctx && ctx.rules && ctx.rules[attachment.question]) || {});

        if (attachment.visualizer) rule.visualizer = attachment.visualizer;
        if (attachment.chart_type) rule.chart_type = attachment.chart_type;

        return {
            id:         attachment.id,
            form:       attachment.form,
            question:   attachment.question,
            visualizer: rule.visualizer || null,
            chart_type: rule.chart_type || null,
            sort:       attachment.sort || 'default',
            rule:       rule,
        };
    }

    /** Deep copy of a section with fresh ids, for "duplicate". */
    function cloneSection(section) {
        return {
            id:          newId('sec'),
            type:        section.type,
            title:       section.title,
            body:        section.body.slice(),
            attachments: section.attachments.map(a => Object.assign({}, a, { id: newId('att') })),
        };
    }

    /* ---------------------------------------------------------------------- *
     |  Layout                                                                |
     * ---------------------------------------------------------------------- */

    /**
     * Which grid a section uses:
     *   rp-l-text   narrative only, full width
     *   rp-l-side   one chart beside the text (2:3)
     *   rp-l-stack  even number of charts, text on top, charts in a 1:1 grid
     *   rp-l-mixed  odd number (3+): first chart beside the text, the rest in a 1:1 grid
     */
    function layoutClass(section) {
        const n = section.type === 'narrative' ? 0 : (section.attachments || []).length;

        if (n === 0) return 'rp-l-text';
        if (n === 1) return 'rp-l-side';
        return n % 2 === 0 ? 'rp-l-stack' : 'rp-l-mixed';
    }

    /** Human label for the section header pill. */
    function attachmentLabel(section) {
        const n = section.type === 'narrative' ? 0 : section.attachments.length;
        return n === 0 ? 'Narrative' : n === 1 ? '1 visual' : n + ' visuals';
    }

    /* ---------------------------------------------------------------------- *
     |  Data                                                                  |
     * ---------------------------------------------------------------------- */

    function isAnswered(value) {
        if (value === null || value === undefined || value === '') return false;
        if (Array.isArray(value)) return value.length > 0;
        if (typeof value === 'object') return Object.keys(value).length > 0;
        return true;
    }

    /** How many submissions answered a question. */
    function responsesFor(questionName, data) {
        return (data || []).reduce((n, row) => n + (row && isAnswered(row[questionName]) ? 1 : 0), 0);
    }

    /** Distinct form ids referenced by the document's visuals. */
    function formsUsed(doc) {
        const ids = new Set();
        (doc.sections || []).forEach(s => (s.attachments || []).forEach(a => { if (a.form) ids.add(a.form); }));
        return Array.from(ids);
    }

    return {
        VERSION, TYPES, SORTS, SORT_LABELS,
        newId, escapeHtml, textToParagraphs, paragraphsToText,
        isLegacy, normalize,
        newSection, newAttachment, resolveAttachment, cloneSection, sortFromRule,
        layoutClass, attachmentLabel,
        responsesFor, formsUsed,
    };

})(window);
