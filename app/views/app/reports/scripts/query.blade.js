/**
 * Report queries (SurveyQL)
 * --------------------------------------------------------------------------
 * Inline answers in report text. A report names its sources (alias -> form)
 * and expressions (name -> SurveyQL), stored in `reports.svql_expressions`:
 *
 *   { sources: [{ name: "hh", form: 12 }], expressions: [{ name: "adults", svql: "hh | filter age >= 18" }] }
 *
 * Paragraphs carry tokens such as `query:{hh | count(all)}` or `query:{adults}`;
 * they are replaced by the answer when the report renders. Only single-line
 * answers are allowed: a scalar, or a table with exactly one row.
 *
 * Queries run in the browser through the SurveyQL query core
 * (public/vendor/surveyql), over each form's rows as loaded (report filters
 * applied), so the numbers always agree with the charts beside them.
 */
window.ReportQuery = (function (global) {
    'use strict';

    const PREFIX = 'query:{';
    const esc = text => ReportDoc.escapeHtml(text);

    const available = () => !!(global.SurveyQL && global.SurveyQL.evaluateReport);

    /** Settings in their canonical shape. */
    function normalize(raw) {
        if (typeof raw === 'string') { try { raw = JSON.parse(raw); } catch (e) { raw = null; } }
        raw = raw && typeof raw === 'object' && !Array.isArray(raw) ? raw : {};
        return {
            sources: (Array.isArray(raw.sources) ? raw.sources : [])
                .filter(s => s && typeof s.name === 'string')
                .map(s => ({ name: s.name, form: parseInt(s.form, 10) || null })),
            expressions: (Array.isArray(raw.expressions) ? raw.expressions : [])
                .filter(e => e && typeof e.name === 'string')
                .map(e => ({ name: e.name, svql: typeof e.svql === 'string' ? e.svql : '' })),
        };
    }

    /**
     * Split text into plain runs and `query:{…}` tokens. Braces nest and quoted
     * strings are skipped (mirrors ReportSchema::queryTokens). An unclosed token
     * runs to the end of the text.
     * @returns {Array<{text: string, query?: string}>}
     */
    function scan(text) {
        text = String(text == null ? '' : text);
        const parts = [];
        let offset = 0;

        for (;;) {
            const start = text.indexOf(PREFIX, offset);
            if (start === -1) break;

            let depth = 0, quote = null, end = text.length, closed = false;
            for (let i = start + PREFIX.length - 1; i < text.length; i++) {
                const c = text[i];
                if (quote) {
                    if (c === '\\') i++;
                    else if (c === quote) quote = null;
                } else if (c === '"' || c === "'") {
                    quote = c;
                } else if (c === '{') {
                    depth++;
                } else if (c === '}' && --depth === 0) {
                    end = i + 1;
                    closed = true;
                    break;
                }
            }

            if (start > offset) parts.push({ text: text.slice(offset, start) });
            const raw = text.slice(start, end);
            parts.push({ text: raw, query: (closed ? raw.slice(PREFIX.length, -1) : raw.slice(PREFIX.length)).trim() });
            offset = end;
        }

        if (offset < text.length) parts.push({ text: text.slice(offset) });
        return parts;
    }

    /** Queries used in one paragraph. */
    const queriesIn = text => scan(text).filter(p => p.query !== undefined).map(p => p.query);

    /** Distinct queries used anywhere in a document. */
    function tokens(doc) {
        const seen = new Set();
        ((doc && doc.sections) || []).forEach(s => (s.body || []).forEach(p => queriesIn(p).forEach(q => seen.add(q))));
        return Array.from(seen);
    }

    /** SurveyQL source inputs: one dataset per named source whose form is loaded. */
    function sourceInputs(svql, ctxs) {
        return svql.sources
            .filter(s => s.form && ctxs[s.form])
            .map(s => ({ name: s.name, survey: ctxs[s.form].json || {}, data: ctxs[s.form].data || [] }));
    }

    /**
     * Evaluate the expressions, plus every token in `doc` and any `extra` queries.
     * Resolves to { sources, expressions, tokens } (each name/query -> answer),
     * or null when the engine is not loaded or there is nothing to answer.
     * Answers are { ok: true, text, value, label } or { ok: false, error }.
     */
    function evaluate(doc, svql, ctxs, extra) {
        svql = normalize(svql);
        const queries = tokens(doc).concat((extra || []).filter(Boolean));
        if (!available()) return Promise.resolve(null);
        if (!queries.length && !svql.expressions.length && !svql.sources.length) return Promise.resolve(null);

        const sources = sourceInputs(svql, ctxs);
        const missing = svql.sources.filter(s => !s.form || !ctxs[s.form]);

        return global.SurveyQL.evaluateReport({ sources, expressions: svql.expressions, tokens: queries })
            .then(out => {
                missing.forEach(s => { out.sources[s.name] = { ok: false, error: 'its form is no longer attached' }; });
                return out;
            })
            .catch(err => {
                console.error('SurveyQL', err);
                return null;
            });
    }

    /**
     * Paragraph text as HTML with tokens replaced by their answers.
     * @param {object|null} results   output of evaluate(); null = none (yet)
     * @param {object} opts           { errors: true } shows failures (editor, preview);
     *                                { pending: true } marks not-yet-evaluated tokens
     */
    function html(text, results, opts) {
        opts = opts || {};
        return scan(text).map(part => {
            if (part.query === undefined) return esc(part.text);

            const answer = results && results.tokens ? results.tokens[part.query] : null;
            if (answer && answer.ok) {
                return '<span class="rp-q" title="' + esc(part.query) + '">' + esc(answer.text) + '</span>';
            }
            if (!answer && opts.pending) {
                return '<span class="rp-q is-pending" title="' + esc(part.query) + '">…</span>';
            }
            if (opts.errors) {
                const why = answer ? answer.error : 'SurveyQL is not available';
                return '<span class="rp-q is-error" title="' + esc(part.query + ' — ' + why) + '"><i class="fa-solid fa-triangle-exclamation"></i> ' + esc(part.text) + '</span>';
            }
            return '<span class="rp-q is-empty">—</span>';
        }).join('');
    }

    /** A source alias for a form, unique within `taken`: its slug or title as an identifier. */
    function suggestName(ctx, taken) {
        let base = String((ctx && (ctx.slug || ctx.title)) || 'form')
            .toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '').slice(0, 24) || 'form';
        if (/^[0-9]/.test(base)) base = 'f_' + base;
        let name = base, i = 2;
        while (taken.indexOf(name) !== -1) name = base + '_' + i++;
        return name;
    }

    return { PREFIX, available, normalize, scan, queriesIn, tokens, evaluate, html, suggestName };

})(window);
