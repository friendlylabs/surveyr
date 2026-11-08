/**
 * Report renderer
 * --------------------------------------------------------------------------
 * Draws a v2 report document as a read-only page: a sticky contents list and
 * numbered sections, each laid out by ReportDoc.layoutClass with its charts
 * mounted through ReportViz. Used by the in-app viewer and the public page.
 * `query:{…}` tokens in paragraphs are filled from opts.queries (ReportQuery).
 */
window.ReportRenderer = (function (global) {
    'use strict';

    const esc = text => ReportDoc.escapeHtml(text);

    function paragraphs(section, opts) {
        if (!section.body.length) return '';
        const text = global.ReportQuery
            ? p => ReportQuery.html(p, opts.queries || null, { errors: !!opts.queryErrors, pending: !!opts.queryPending })
            : esc;
        return section.body.map(p => '<p class="rp-p">' + text(p) + '</p>').join('');
    }

    function attachmentCard(attachment, ctxs) {
        const ctx = ctxs[attachment.form];
        const question = ctx && ctx.survey ? ctx.survey.getQuestionByName(attachment.question) : null;
        const title = question ? (question.title || question.name) : attachment.question;
        const n = ctx ? ReportDoc.responsesFor(attachment.question, ctx.data) : 0;
        const source = ctx ? ctx.title : 'Form removed';

        return '<div class="rp-att" data-att="' + esc(attachment.id) + '">'
            +   '<div class="rp-att-head"><span class="rp-att-title">' + esc(title) + '</span></div>'
            +   '<div class="rp-att-slot"></div>'
            +   '<span class="rp-att-foot">' + esc(source) + ' · ' + n + ' response' + (n === 1 ? '' : 's') + '</span>'
            + '</div>';
    }

    function sectionGrid(section, ctxs, opts) {
        const layout = ReportDoc.layoutClass(section);
        const atts = section.type === 'narrative' ? [] : section.attachments;

        let html = '<div class="rp-grid ' + layout + '">';
        html += '<div class="rp-text">' + paragraphs(section, opts) + '</div>';

        if (layout === 'rp-l-side' || layout === 'rp-l-mixed') {
            html += attachmentCard(atts[0], ctxs);
        }

        const rest = layout === 'rp-l-stack' ? atts : layout === 'rp-l-mixed' ? atts.slice(1) : [];
        if (rest.length) {
            html += '<div class="rp-atts-rest">' + rest.map(a => attachmentCard(a, ctxs)).join('') + '</div>';
        }

        return html + '</div>';
    }

    function sectionHtml(section, index, ctxs, opts) {
        return '<section class="rp-section" id="' + esc(section.id) + '">'
            +   '<h2 class="rp-h2"><span class="rp-h2-n">' + (index + 1) + '.</span>' + esc(section.title || 'Untitled section') + '</h2>'
            +   sectionGrid(section, ctxs, opts)
            + '</section>';
    }

    /**
     * @param {HTMLElement} host
     * @param {object} doc      v2 report document
     * @param {object} ctxs     { [formId]: { id, title, survey, data, rules } }
     * @param {object} opts     { title, emptyHtml, queries, queryErrors, queryPending }
     *                          queries: ReportQuery.evaluate() output for the document
     * @returns {Array} mounted chart handles
     */
    function render(host, doc, ctxs, opts) {
        opts = opts || {};
        doc = ReportDoc.normalize(doc);

        if (!doc.sections.length) {
            host.innerHTML = opts.emptyHtml || '<div class="rp-att-notice">This report has no content yet.</div>';
            return [];
        }

        const toc = doc.sections.map((s, i) =>
            '<a href="#' + esc(s.id) + '"><span class="rp-toc-n">' + (i + 1) + '</span>' + esc(s.title || 'Untitled section') + '</a>'
        ).join('');

        const sections = doc.sections.map((s, i) => sectionHtml(s, i, ctxs, opts)).join('');

        host.innerHTML = '<div class="rp-doc">'
            +   '<nav class="rp-toc"><span class="rp-kicker">Contents</span>' + toc + '</nav>'
            +   '<div class="rp-body">'
            +     (opts.title ? '<h1 class="rp-h1">' + esc(opts.title) + '</h1>' : '')
            +     sections
            +   '</div>'
            + '</div>';

        // charts mount once their slots are in the document and sized
        const handles = [];
        doc.sections.forEach(section => {
            if (section.type === 'narrative') return;
            section.attachments.forEach(attachment => {
                const slot = host.querySelector('[data-att="' + attachment.id + '"] .rp-att-slot');
                if (!slot) return;
                handles.push(ReportViz.mount(slot, ReportDoc.resolveAttachment(attachment, ctxs), ctxs[attachment.form]));
            });
        });

        return handles;
    }

    /** Re-fill the query tokens of an already rendered document, leaving charts alone. */
    function fillQueries(host, doc, opts) {
        doc = ReportDoc.normalize(doc);
        doc.sections.forEach(section => {
            const text = host.querySelector('.rp-section[id="' + section.id + '"] .rp-text');
            if (text) text.innerHTML = paragraphs(section, opts || {});
        });
    }

    return { render, fillQueries };

})(window);
