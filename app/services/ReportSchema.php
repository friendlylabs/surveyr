<?php

namespace App\Services;

/**
 * Report document schema (v2)
 * --------------------------------------------------------------------------
 * Pure helpers for the JSON stored in `reports.content` / `reports.draft`.
 *
 *   {
 *     "version": 2,
 *     "sections": [{
 *       "id": "sec_xxxxxx",
 *       "type": "narrative" | "visual",
 *       "title": "…",
 *       "body": ["paragraph", …],
 *       "attachments": [{
 *         "id": "att_xxxxxx",
 *         "form": 12,                          // source form id (reports span several forms)
 *         "question": "<surveyjs question name in that form>",
 *         "visualizer": "selectBase" | null,   // null = use the form's viz rule
 *         "chart_type": "bar" | null,
 *         "sort": "default" | "desc" | "asc"
 *       }]
 *     }]
 *   }
 *
 * `reports.filters` is keyed by form id:
 *   { "12": { from_date, to_date, flag, "<question>": [rules] }, "15": { … } }
 *
 * Legacy (v1) documents looked like
 *   { menu: [...], sections: [{ id, title, description: "text\nwith newlines", attachments: ["questionName"] }] }
 * with a flat, single-form filter object. Both are converted on the fly by
 * normalize() / normalizeFilters(), or persisted by `reports:migrate`.
 */
class ReportSchema
{
    public const VERSION = 2;

    public const TYPES = ['narrative', 'visual'];
    public const SORTS = ['default', 'desc', 'asc'];

    private const FIXED_FILTERS = ['from_date', 'to_date', 'flag'];

    /** An empty, valid document. */
    public static function empty(): array
    {
        return ['version' => self::VERSION, 'sections' => []];
    }

    /** Short random id, e.g. "sec_k3f9a1". */
    public static function id(string $prefix): string
    {
        return $prefix . '_' . substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyz'), 0, 6);
    }

    /** Whether the raw value is a v1 document (has sections but no version 2 marker). */
    public static function isLegacy(mixed $raw): bool
    {
        if (!is_array($raw) || !isset($raw['sections']) || !is_array($raw['sections'])) {
            return false;
        }

        // v1 docs never carried a version marker
        return ($raw['version'] ?? null) !== self::VERSION;
    }

    /**
     * Whether a stored document already has the latest shape: v2 with a
     * form on every attachment. Anything else is worth migrating.
     */
    public static function isCurrent(mixed $raw): bool
    {
        if (!is_array($raw) || empty($raw['sections'])) return true;   // nothing to convert
        if (self::isLegacy($raw)) return false;

        foreach ($raw['sections'] as $section) {
            foreach ($section['attachments'] ?? [] as $attachment) {
                if (!is_array($attachment) || empty($attachment['form'])) return false;
            }
        }

        return true;
    }

    /** Whether stored filters are already keyed by form id. */
    public static function filtersAreCurrent(mixed $raw): bool
    {
        if (!is_array($raw) || !$raw) return true;

        foreach (array_keys($raw) as $key) {
            if (!ctype_digit((string) $key)) return false;
        }

        return true;
    }

    /** Convert a v1 document into v2, stamping every attachment with the legacy form. */
    public static function fromLegacy(array $raw, ?int $defaultFormId = null): array
    {
        $sections = [];

        foreach ($raw['sections'] ?? [] as $section) {
            if (!is_array($section)) continue;

            $attachments = [];
            foreach ($section['attachments'] ?? [] as $attachment) {
                $question = is_array($attachment) ? ($attachment['question'] ?? null) : $attachment;
                if (!is_string($question) || $question === '') continue;

                $attachments[] = [
                    'id'         => self::id('att'),
                    'form'       => is_array($attachment) ? (int) ($attachment['form'] ?? $defaultFormId) : (int) $defaultFormId,
                    'question'   => $question,
                    'visualizer' => null,
                    'chart_type' => null,
                    'sort'       => 'default',
                ];
            }

            $sections[] = [
                'id'          => is_string($section['id'] ?? null) && $section['id'] !== '' ? $section['id'] : self::id('sec'),
                'type'        => $attachments ? 'visual' : 'narrative',
                'title'       => trim((string) ($section['title'] ?? '')),
                'body'        => is_array($section['body'] ?? null)
                    ? array_values(array_filter(array_map('trim', array_filter($section['body'], 'is_scalar')), fn ($p) => $p !== ''))
                    : self::paragraphs((string) ($section['description'] ?? '')),
                'attachments' => $attachments,
            ];
        }

        return ['version' => self::VERSION, 'sections' => $sections];
    }

    /**
     * Coerce anything into a complete v2 document. Accepts null, [] (what the
     * json cast yields for the "{}" column default), v1 and v2 arrays.
     * Attachments without a resolvable form are dropped.
     */
    public static function normalize(mixed $raw, ?int $defaultFormId = null): array
    {
        if (!is_array($raw) || empty($raw['sections']) || !is_array($raw['sections'])) {
            return self::empty();
        }

        if (self::isLegacy($raw)) {
            $raw = self::fromLegacy($raw, $defaultFormId);
        }

        $sections = [];
        $seen = [];

        foreach ($raw['sections'] as $section) {
            if (!is_array($section)) continue;

            $id = is_string($section['id'] ?? null) && $section['id'] !== '' ? $section['id'] : self::id('sec');
            while (isset($seen[$id])) $id = self::id('sec');
            $seen[$id] = true;

            $attachments = [];
            foreach ($section['attachments'] ?? [] as $attachment) {
                if (!is_array($attachment)) continue;

                $question = $attachment['question'] ?? null;
                if (!is_string($question) || $question === '') continue;

                $form = (int) ($attachment['form'] ?? $defaultFormId ?? 0);
                if ($form <= 0) continue;

                $attachments[] = [
                    'id'         => is_string($attachment['id'] ?? null) && $attachment['id'] !== '' ? $attachment['id'] : self::id('att'),
                    'form'       => $form,
                    'question'   => $question,
                    'visualizer' => self::shortString($attachment['visualizer'] ?? null),
                    'chart_type' => self::shortString($attachment['chart_type'] ?? null),
                    'sort'       => in_array($attachment['sort'] ?? null, self::SORTS, true) ? $attachment['sort'] : 'default',
                ];
            }

            $type = in_array($section['type'] ?? null, self::TYPES, true)
                ? $section['type']
                : ($attachments ? 'visual' : 'narrative');

            $body = $section['body'] ?? [];
            if (is_string($body)) $body = self::paragraphs($body);
            if (!is_array($body)) $body = [];

            $sections[] = [
                'id'          => $id,
                'type'        => $type,
                'title'       => trim((string) ($section['title'] ?? '')),
                'body'        => array_values(array_filter(array_map(
                    fn ($p) => is_scalar($p) ? trim((string) $p) : '', $body
                ), fn ($p) => $p !== '')),
                'attachments' => $type === 'narrative' ? [] : $attachments,
            ];
        }

        return ['version' => self::VERSION, 'sections' => $sections];
    }

    /**
     * Normalize and scrub a document coming from the browser: strip markup
     * from text and drop attachments whose form is not attached to the
     * report or whose question that form no longer has.
     *
     * @param array $questionNamesByForm  [formId => [question names]]
     */
    public static function sanitize(mixed $raw, array $questionNamesByForm, ?int $defaultFormId = null): array
    {
        $doc = self::normalize($raw, $defaultFormId);

        $allowed = [];
        foreach ($questionNamesByForm as $formId => $names) {
            $allowed[(int) $formId] = array_flip($names);
        }

        foreach ($doc['sections'] as &$section) {
            $section['title'] = self::text($section['title']);
            $section['body']  = array_values(array_filter(
                array_map(fn ($p) => self::text($p), $section['body']),
                fn ($p) => $p !== ''
            ));
            $section['attachments'] = array_values(array_filter(
                $section['attachments'],
                fn ($a) => isset($allowed[$a['form']][$a['question']])
            ));
        }

        return $doc;
    }

    /**
     * Coerce stored filters into { formId: {…} }. Flat legacy filters are
     * wrapped under the legacy form; forms not attached are dropped.
     *
     * @param int[] $formIds  forms attached to the report
     */
    public static function normalizeFilters(mixed $raw, array $formIds, ?int $legacyFormId = null): array
    {
        if (!is_array($raw) || !$raw) return [];

        if (!self::filtersAreCurrent($raw)) {
            $raw = $legacyFormId ? [(string) $legacyFormId => $raw] : [];
        }

        $allowed = array_flip(array_map('intval', $formIds));
        $filters = [];

        foreach ($raw as $formId => $set) {
            $formId = (int) $formId;
            if (!isset($allowed[$formId]) || !is_array($set)) continue;

            $clean = [];
            foreach ($set as $key => $value) {
                if ($key === 'review_type') {
                    // legacy review states have no flag equivalent
                    continue;
                } elseif ($key === 'flag') {
                    // Collection::formCollections() expects a list of flag keys
                    $value = array_values(array_filter(array_map('strval', (array) $value), 'strlen'));
                    if ($value) $clean[$key] = $value;
                } elseif (in_array($key, self::FIXED_FILTERS, true)) {
                    if (is_scalar($value) && (string) $value !== '') $clean[$key] = (string) $value;
                } elseif (is_array($value) && $value) {
                    $clean[$key] = array_values(array_filter($value, 'is_array'));
                    if (!$clean[$key]) unset($clean[$key]);
                }
            }

            if ($clean) $filters[(string) $formId] = $clean;
        }

        return $filters;
    }

    /** Distinct form ids referenced by a document's attachments. */
    public static function formsUsed(array $doc): array
    {
        $ids = [];
        foreach ($doc['sections'] ?? [] as $section) {
            foreach ($section['attachments'] ?? [] as $attachment) {
                if (!empty($attachment['form'])) $ids[(int) $attachment['form']] = true;
            }
        }

        return array_keys($ids);
    }

    /** Question names a document uses, per form: [formId => [names]]. */
    public static function questionsUsed(array $doc): array
    {
        $used = [];
        foreach ($doc['sections'] ?? [] as $section) {
            foreach ($section['attachments'] ?? [] as $attachment) {
                if (empty($attachment['form']) || empty($attachment['question'])) continue;
                $used[(int) $attachment['form']][$attachment['question']] = true;
            }
        }

        return array_map('array_keys', $used);
    }

    /**
     * Content-only signature of a document, ignoring ids, so two copies can be
     * compared even when one was converted from the legacy schema.
     */
    public static function fingerprint(mixed $raw, ?int $defaultFormId = null): string
    {
        $doc = self::normalize($raw, $defaultFormId);
        $bare = array_map(fn ($s) => [
            $s['type'], $s['title'], $s['body'],
            array_map(fn ($a) => [$a['form'], $a['question'], $a['visualizer'], $a['chart_type'], $a['sort']], $s['attachments']),
        ], $doc['sections']);

        return md5(json_encode($bare));
    }

    /** Every question name in a SurveyJS definition, panels and dynamic panels included. */
    public static function questionNames(mixed $survey): array
    {
        $names = [];
        $walk = function ($elements) use (&$walk, &$names) {
            if (!is_array($elements)) return;
            foreach ($elements as $element) {
                if (!is_array($element)) continue;
                if (isset($element['name']) && is_string($element['name']) && ($element['type'] ?? 'panel') !== 'panel') {
                    $names[] = $element['name'];
                }
                $walk($element['elements'] ?? null);
                $walk($element['templateElements'] ?? null);
            }
        };

        if (is_array($survey)) {
            $walk($survey['pages'] ?? null);
            $walk($survey['elements'] ?? null);
        }

        return array_values(array_unique($names));
    }

    /** Plain text split into paragraphs on blank lines / newlines; legacy CKEditor markup is flattened. */
    public static function paragraphs(string $text): array
    {
        $text = preg_replace('/<br\s*\/?>|<\/p>/i', "\n", $text);
        $text = str_replace("\u{00A0}", ' ', self::text($text));

        $parts = preg_split('/\R+/u', $text) ?: [];
        return array_values(array_filter(array_map('trim', $parts), fn ($p) => $p !== ''));
    }

    /* ---------------------------------------------------------------------- *
     |  SurveyQL                                                              |
     * ---------------------------------------------------------------------- */

    /** Empty `reports.svql_expressions`. */
    public static function emptySvql(): array
    {
        return ['sources' => [], 'expressions' => []];
    }

    /**
     * Coerce stored/posted SurveyQL settings into
     *   { sources: [{name, form}], expressions: [{name, svql}] }
     * Names must be identifiers that are unique and not SurveyQL words; sources
     * pointing at forms not attached to the report are dropped. Queries are kept
     * verbatim (they are only ever rendered escaped).
     *
     * @param int[] $formIds  forms attached to the report
     */
    public static function normalizeSvql(mixed $raw, array $formIds): array
    {
        if (is_string($raw)) $raw = json_decode($raw, true);
        if (!is_array($raw)) return self::emptySvql();

        $allowed = array_flip(array_map('intval', $formIds));
        $taken = [];
        $valid = function (mixed $name) use (&$taken): ?string {
            if (!is_string($name)) return null;
            $name = trim($name);
            if (!preg_match(self::SVQL_NAME, $name) || in_array(strtolower($name), self::SVQL_RESERVED, true) || isset($taken[$name])) {
                return null;
            }
            return $taken[$name] = $name;
        };

        $sources = [];
        foreach (array_slice((array) ($raw['sources'] ?? []), 0, self::SVQL_MAX_SOURCES) as $source) {
            if (!is_array($source)) continue;
            $form = (int) ($source['form'] ?? 0);
            if (!isset($allowed[$form])) continue;
            $name = $valid($source['name'] ?? null);
            if ($name !== null) $sources[] = ['name' => $name, 'form' => $form];
        }

        $expressions = [];
        foreach (array_slice((array) ($raw['expressions'] ?? []), 0, self::SVQL_MAX_EXPRESSIONS) as $expression) {
            if (!is_array($expression) || !is_scalar($expression['svql'] ?? null)) continue;
            $svql = self::query((string) $expression['svql']);
            if ($svql === '') continue;
            $name = $valid($expression['name'] ?? null);
            if ($name !== null) $expressions[] = ['name' => $name, 'svql' => $svql];
        }

        return ['sources' => $sources, 'expressions' => $expressions];
    }

    /** Form ids that SurveyQL sources read from. */
    public static function svqlForms(array $svql): array
    {
        return array_values(array_unique(array_map(fn ($s) => (int) $s['form'], $svql['sources'] ?? [])));
    }

    /**
     * Byte spans [start, length] of every `query:{…}` token in a paragraph.
     * Braces nest and quoted strings are skipped; an unclosed token runs to the end.
     */
    public static function queryTokens(string $text): array
    {
        $spans = [];
        $offset = 0;
        while (($start = strpos($text, 'query:{', $offset)) !== false) {
            $depth = 0;
            $quote = null;
            $end = strlen($text);
            for ($i = $start + 6, $n = strlen($text); $i < $n; $i++) {
                $c = $text[$i];
                if ($quote !== null) {
                    if ($c === '\\') $i++;
                    elseif ($c === $quote) $quote = null;
                } elseif ($c === '"' || $c === "'") {
                    $quote = $c;
                } elseif ($c === '{') {
                    $depth++;
                } elseif ($c === '}' && --$depth === 0) {
                    $end = $i + 1;
                    break;
                }
            }
            $spans[] = [$start, $end - $start];
            $offset = $end;
        }

        return $spans;
    }

    private const SVQL_NAME = '/^[A-Za-z_][A-Za-z0-9_]{0,39}$/';
    private const SVQL_MAX_SOURCES = 20;
    private const SVQL_MAX_EXPRESSIONS = 50;
    private const SVQL_MAX_LENGTH = 1000;

    /** SurveyQL commands and keywords; mirrors the names the engine itself refuses. */
    private const SVQL_RESERVED = [
        'load', 'use', 'sources', 'datasets', 'union', 'join', 'rename', 'questions', 'columns', 'show', 'head',
        'sample', 'count', 'freq', 'avg', 'mean', 'sum', 'min', 'max', 'median', 'stddev', 'describe', 'summary',
        'get', 'select', 'filter', 'print', 'echo', 'save', 'export', 'draw', 'let',
        'where', 'by', 'against', 'with', 'on', 'as', 'all', 'and', 'or', 'not', 'in', 'is', 'contains', 'between',
        'true', 'false', 'null',
    ];

    /** A query kept verbatim (request escaping undone): control characters dropped, length capped. */
    private static function query(string $svql): string
    {
        $svql = html_entity_decode($svql, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $svql = trim(preg_replace('/[^\P{C}\n\t]/u', '', $svql) ?? '');
        return mb_substr($svql, 0, self::SVQL_MAX_LENGTH);
    }

    /** Strip markup, but leave `query:{…}` tokens intact (`age<18` would read as a tag). */
    private static function text(mixed $value): string
    {
        $value = (string) $value;
        $kept = [];
        foreach (array_reverse(self::queryTokens($value)) as [$start, $length]) {
            $key = "\u{E000}" . count($kept) . "\u{E001}";
            $kept[$key] = substr($value, $start, $length);
            $value = substr_replace($value, $key, $start, $length);
        }

        $decode = fn ($text) => html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return strtr(trim($decode(strip_tags($value))), array_map($decode, $kept));
    }

    private static function shortString(mixed $value): ?string
    {
        if (!is_string($value)) return null;
        $value = trim($value);
        return ($value === '' || strlen($value) > 40 || !preg_match('/^[a-zA-Z0-9_\-]+$/', $value)) ? null : $value;
    }
}
