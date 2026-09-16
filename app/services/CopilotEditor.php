<?php

namespace App\Services;

use App\Utils\OpenaiUtil;

/**
 * The chat-driven form editor behind the creator's Copilot panel.
 *
 * One request = one conversational turn: the client sends the instruction,
 * the full survey JSON and the earlier turns of the same conversation; this
 * runs an OpenAI tool-calling loop over a compact OUTLINE of the survey and
 * returns an ordered list of operations for the client to apply through the
 * SurveyJS Creator API (see `copilot.blade.js`'s `applyOperations`). The
 * document itself is never mutated here — the client decides whether to
 * apply a proposal.
 *
 * Only two tools exist: `read_question` (resolved in-process against the
 * document sent with the request) and `submit_operations` (the only tool
 * that produces an edit). The element types and their properties come from
 * SurveyJS's own metadata, gathered client-side (`buildTypeSchema`) for the
 * types the survey actually uses, so the system prompt stays byte-stable.
 */
class CopilotEditor
{
    private const MAX_ITERATIONS = 8;

    /**
     * Compact names/types/titles-only view of the survey — enough for the
     * model to reference pages and questions by name without paying for
     * every property on every turn.
     *
     * @param array $survey The full SurveyJS JSON.
     * @return array
     */
    public static function buildOutline(array $survey): array
    {
        $outline = ['pages' => []];
        $title = self::trimTitle($survey['title'] ?? null);
        if ($title !== null) $outline['title'] = $title;

        foreach ($survey['pages'] ?? [] as $page) {
            if (!is_array($page)) continue;
            $node = ['name' => $page['name'] ?? '', 'elements' => self::outlineElements($page['elements'] ?? [])];
            $pageTitle = self::trimTitle($page['title'] ?? null);
            if ($pageTitle !== null) $node['title'] = $pageTitle;
            $outline['pages'][] = $node;
        }

        return $outline;
    }

    private static function outlineElements(array $elements): array
    {
        $out = [];
        foreach ($elements as $el) {
            if (!is_array($el)) continue;
            $node = ['name' => $el['name'] ?? '', 'type' => $el['type'] ?? ''];
            $title = self::trimTitle($el['title'] ?? null);
            if ($title !== null) $node['title'] = $title;
            if (!empty($el['elements']) && is_array($el['elements'])) {
                $node['elements'] = self::outlineElements($el['elements']);
            }
            $out[] = $node;
        }
        return $out;
    }

    private static function trimTitle($title): ?string
    {
        if (!is_string($title)) return null;
        $title = trim($title);
        if ($title === '') return null;
        return mb_strlen($title) > 60 ? mb_substr($title, 0, 57) . '...' : $title;
    }

    /**
     * Runs one conversational turn.
     *
     * @param array  $document    The full SurveyJS JSON (used to resolve `read_question`).
     * @param string $instruction The user's message.
     * @param array  $history     Earlier `{role: user|assistant, text}` turns, oldest first.
     * @param array  $schema      SurveyJS metadata gathered client-side: `{types, common, present}`.
     * @return array{operations: array, note: ?string, usage: ?array, iterations: int, truncated: bool}
     */
    public function run(array $document, string $instruction, array $history = [], array $schema = []): array
    {
        $outline = self::buildOutline($document);

        $messages = [['role' => 'system', 'content' => OpenaiUtil::getSystemMessage()]];
        foreach ($history as $turn) {
            $messages[] = ['role' => $turn['role'], 'content' => $turn['text']];
        }
        $messages[] = ['role' => 'user', 'content' => "FORM_OUTLINE:\n" . json_encode($outline, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)];
        $typeSchemaMessage = $this->buildTypeSchemaMessage($schema);
        if ($typeSchemaMessage !== null) {
            $messages[] = ['role' => 'user', 'content' => $typeSchemaMessage];
        }
        $messages[] = ['role' => 'user', 'content' => "INSTRUCTION:\n" . $instruction];

        $operations = [];
        $usage = null;
        $note = null;
        $calledTools = [];

        for ($i = 0; $i < self::MAX_ITERATIONS; $i++) {
            $response = OpenaiUtil::chatCompletion([
                'model' => OpenaiUtil::textModel(),
                'messages' => $messages,
                'tools' => $this->toolsSchema(),
                'tool_choice' => 'auto',
                'temperature' => 0,
            ]);

            $usage = $response['usage'] ?? $usage;
            $assistantMessage = $response['choices'][0]['message'] ?? null;
            if (!$assistantMessage) {
                throw new \RuntimeException('OpenAI returned no message.');
            }

            $messages[] = $assistantMessage;
            $toolCalls = $assistantMessage['tool_calls'] ?? [];

            // Captured on every turn: the model's explanation for a decline (or its
            // summary of the edit) rides along with the submit_operations call itself.
            if (is_string($assistantMessage['content'] ?? null) && trim($assistantMessage['content']) !== '') {
                $note = trim($assistantMessage['content']);
            }

            if (empty($toolCalls)) {
                break;
            }

            $submitted = false;
            foreach ($toolCalls as $toolCall) {
                $functionName = $toolCall['function']['name'] ?? '';
                $args = json_decode($toolCall['function']['arguments'] ?? '{}', true) ?: [];
                $toolCallId = $toolCall['id'] ?? '';
                $calledTools[] = $functionName;

                if ($functionName === 'read_question') {
                    $question = $this->findQuestion($document, $args['name'] ?? '');
                    $messages[] = [
                        'role' => 'tool',
                        'tool_call_id' => $toolCallId,
                        'content' => $question === null ? json_encode(['error' => 'not_found']) : json_encode($question, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    ];
                } elseif ($functionName === 'submit_operations') {
                    if (getenv("COPILOT_DEBUG")) file_put_contents(getenv("TEMP") . "/copilot-raw.json", ($toolCall["function"]["arguments"] ?? "") . "
", FILE_APPEND);
                    // Only the first submit in a message counts; a repeated call would duplicate the batch
                    foreach (($submitted ? [] : ($args['operations'] ?? [])) as $rawOp) {
                        $translated = is_array($rawOp) ? $this->translateOperation($rawOp) : null;
                        if ($translated !== null) {
                            $operations[] = $translated;
                        }
                    }
                    $operations = $this->normalizeOperations($operations, $document);
                    $messages[] = ['role' => 'tool', 'tool_call_id' => $toolCallId, 'content' => json_encode(['ok' => true])];
                    $submitted = true;
                } else {
                    $messages[] = ['role' => 'tool', 'tool_call_id' => $toolCallId, 'content' => json_encode(['error' => 'unknown_tool'])];
                }
            }

            if ($submitted) {
                return ['operations' => $operations, 'note' => $note, 'usage' => $usage, 'iterations' => $i + 1, 'truncated' => false];
            }
        }

        $fallback = 'Reached the iteration limit (' . self::MAX_ITERATIONS . ') before the model called submit_operations.';
        if ($calledTools !== []) {
            $fallback .= ' Tools called: ' . implode(', ', $calledTools) . '.';
        }
        return ['operations' => $operations, 'note' => $note ?? $fallback, 'usage' => $usage, 'iterations' => self::MAX_ITERATIONS, 'truncated' => true];
    }

    /**
     * Translates one `{op, args}` tool-call op into the camelCase operation
     * shape the client applies. Items are flat (`{op, page_name, spec, ...}`);
     * a `{op, args: {...}}` wrapper is accepted too, and a `spec`/`patch`
     * flattened into the item is recovered from whatever isn't a positional
     * field. Unknown ops are dropped, not fatal.
     */
    private function translateOperation(array $rawOp): ?array
    {
        $op = $rawOp['op'] ?? '';
        $args = is_array($rawOp['args'] ?? null) ? $rawOp['args'] : array_diff_key($rawOp, ['op' => 1]);
        $clean = static fn (array $a) => array_filter($a, static fn ($v) => $v !== null);

        // `spec`/`patch` if given, otherwise the args minus the positional keys
        $bag = static function (string $key, array $positional) use ($args): array {
            if (is_array($args[$key] ?? null)) return $args[$key];
            return array_diff_key($args, array_flip($positional), ['spec' => 1, 'patch' => 1, 'op' => 1]);
        };

        return match ($op) {
            'update_question' => ['op' => 'updateQuestion', 'name' => $args['name'] ?? '', 'patch' => $bag('patch', ['name'])],
            'update_page' => ['op' => 'updatePage', 'name' => $args['name'] ?? '', 'patch' => $bag('patch', ['name'])],
            'add_question' => $clean([
                'op' => 'addQuestion',
                'pageName' => $args['page_name'] ?? '',
                'afterQuestionName' => $args['after_question_name'] ?? null,
                'beforeQuestionName' => $args['before_question_name'] ?? null,
                'spec' => $bag('spec', ['page_name', 'after_question_name', 'before_question_name']),
            ]),
            'remove_question' => ['op' => 'removeQuestion', 'name' => $args['name'] ?? ''],
            'add_page' => $clean([
                'op' => 'addPage',
                'afterPageName' => $args['after_page_name'] ?? null,
                'beforePageName' => $args['before_page_name'] ?? null,
                'spec' => $bag('spec', ['after_page_name', 'before_page_name']),
            ]),
            'remove_page' => ['op' => 'removePage', 'name' => $args['name'] ?? ''],
            'move_question' => $clean([
                'op' => 'moveQuestion',
                'name' => $args['name'] ?? '',
                'toPageName' => $args['target_page'] ?? '',
                'afterQuestionName' => $args['after_question_name'] ?? null,
                'beforeQuestionName' => $args['before_question_name'] ?? null,
            ]),
            'move_page' => $clean([
                'op' => 'movePage',
                'name' => $args['name'] ?? '',
                'afterPageName' => $args['after_page_name'] ?? null,
                'beforePageName' => $args['before_page_name'] ?? null,
            ]),
            'update_document' => ['op' => 'updateDocument', 'patch' => $bag('patch', [])],
            default => null,
        };
    }

    /**
     * Tidies a submitted batch so one careless op doesn't sink an otherwise
     * good proposal (the client applies a batch atomically): drops empty
     * patches, names unnamed new pages, and removes add_page/add_question ops
     * that duplicate something already created earlier in the same batch (models
     * sometimes nest questions in add_page AND emit them again separately).
     */
    private function normalizeOperations(array $operations, array $document): array
    {
        $added = [];
        $collect = function (array $elements) use (&$collect, &$added): void {
            foreach ($elements as $el) {
                if (!is_array($el)) continue;
                if (!empty($el['name'])) $added[$el['name']] = true;
                if (!empty($el['elements']) && is_array($el['elements'])) $collect($el['elements']);
            }
        };

        $pageCount = count($document['pages'] ?? []);
        $out = [];
        foreach ($operations as $op) {
            switch ($op['op']) {
                case 'updateQuestion':
                case 'updatePage':
                case 'updateDocument':
                    if ($op['patch'] === []) continue 2;
                    break;
                case 'addPage':
                    if (empty($op['spec']['name'])) {
                        $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '_', (string) ($op['spec']['title'] ?? '')), '_'));
                        $op['spec']['name'] = $slug !== '' ? $slug : 'page' . (++$pageCount);
                    }
                    if (isset($added[$op['spec']['name']])) continue 2;
                    $added[$op['spec']['name']] = true;
                    $collect($op['spec']['elements'] ?? []);
                    break;
                case 'addQuestion':
                    $name = $op['spec']['name'] ?? '';
                    if ($name !== '' && isset($added[$name])) continue 2;
                    $collect([$op['spec']]);
                    break;
            }
            $out[] = $op;
        }
        return $out;
    }

    /** Locates a question by name anywhere in the document, recursing into panels. */
    private function findQuestion(array $document, string $name): ?array
    {
        $search = function (array $elements) use (&$search, $name): ?array {
            foreach ($elements as $el) {
                if (!is_array($el)) continue;
                if (($el['name'] ?? null) === $name) return $el;
                if (!empty($el['elements']) && is_array($el['elements'])) {
                    $hit = $search($el['elements']);
                    if ($hit !== null) return $hit;
                }
            }
            return null;
        };

        foreach ($document['pages'] ?? [] as $page) {
            if (!is_array($page)) continue;
            $hit = $search($page['elements'] ?? []);
            if ($hit !== null) return $hit;
        }
        return null;
    }

    /**
     * The per-request FORM_TYPE_SCHEMA message, straight from SurveyJS's own
     * metadata: the element types the creator offers, the properties every
     * question shares, and the extra properties of each type this form uses
     * (`name:type(choice|choice)` shorthand). Null when the client sent nothing.
     */
    private function buildTypeSchemaMessage(array $schema): ?string
    {
        $types = array_values(array_filter($schema['types'] ?? [], 'is_string'));
        $common = array_values(array_filter($schema['common'] ?? [], 'is_string'));
        $present = is_array($schema['present'] ?? null) ? $schema['present'] : [];

        if ($types === [] && $common === [] && $present === []) {
            return null;
        }

        $lines = ["FORM_TYPE_SCHEMA (from SurveyJS metadata, property shorthand is name:type(allowed|values)):"];
        if ($types !== []) $lines[] = "Valid element types: " . implode(', ', $types);
        if ($common !== []) $lines[] = "Properties every question has: " . implode(', ', $common);
        foreach ($present as $type => $props) {
            if (!is_string($type) || !is_array($props)) continue;
            $props = array_values(array_filter($props, 'is_string'));
            $lines[] = "Extra properties of {$type}: " . ($props === [] ? '(none)' : implode(', ', $props));
        }

        return implode("
", $lines);
    }

    private function toolsSchema(): array
    {
        return [
            $this->tool('read_question', 'Fetch the full current spec for one question by name. Call this BEFORE embedding an update_question op that touches existing properties (choices, rows, columns, validators, visibleIf, etc). Returns the question JSON or {"error":"not_found"}.', [
                'name' => ['type' => 'string', 'description' => 'Question name as it appears in the outline.'],
            ], ['name']),
            $this->tool(
                'submit_operations',
                'Emit the final ordered list of edits to apply. Call this EXACTLY ONCE when done planning — this is the ONLY tool that produces an edit; there are no separate add_page/add_question/update_question/etc tools to call. Each item in `operations` is one edit, identified by `op`, with that op\'s own fields alongside it: '
                    . 'update_question {name, patch} — shallow-merge patch onto the existing question, keys you omit are untouched; '
                    . 'update_page {name, patch} — same shallow-merge semantics for a page; '
                    . 'add_question {page_name, spec, before_question_name | after_question_name} — spec is the full question JSON (type + name required, snake_case, unique); positioned immediately before/after the named question, else appended to the page; '
                    . 'remove_question {name}; '
                    . 'add_page {spec, before_page_name | after_page_name} — spec must include name (snake_case, unique) and usually elements (the page\'s questions); positioned before/after the named page, else appended; '
                    . 'remove_page {name} — removes the page and every question on it; '
                    . 'move_question {name, target_page, before_question_name | after_question_name} — repositions an EXISTING question (target_page may be its current page for a plain reorder); '
                    . 'move_page {name, before_page_name | after_page_name} — repositions an EXISTING page; '
                    . 'update_document {patch} — the SURVEY\'s own title/description only. '
                    . 'Use move_* to reorder what already exists, never remove+add. Do not include read_question calls in this list.',
                [
                    'operations' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'op' => ['type' => 'string', 'enum' => ['update_question', 'update_page', 'add_question', 'remove_question', 'add_page', 'remove_page', 'move_question', 'move_page', 'update_document']],
                                'name' => ['type' => 'string', 'description' => 'Existing question/page name (update_*, remove_*, move_*).'],
                                'patch' => ['type' => 'object', 'description' => 'Properties to shallow-merge (update_question, update_page, update_document).'],
                                'page_name' => ['type' => 'string', 'description' => 'Page to add the question to (add_question).'],
                                'target_page' => ['type' => 'string', 'description' => 'Page to move the question to, may be its current page (move_question).'],
                                'spec' => ['type' => 'object', 'description' => 'Full JSON of the new question or page (add_question, add_page).'],
                                'before_question_name' => ['type' => 'string', 'description' => 'Place the question immediately BEFORE this existing question (add_question, move_question).'],
                                'after_question_name' => ['type' => 'string', 'description' => 'Place the question immediately AFTER this existing question (add_question, move_question).'],
                                'before_page_name' => ['type' => 'string', 'description' => 'Place the page immediately BEFORE this existing page (add_page, move_page).'],
                                'after_page_name' => ['type' => 'string', 'description' => 'Place the page immediately AFTER this existing page (add_page, move_page).'],
                            ],
                            'required' => ['op'],
                        ],
                    ],
                ],
                ['operations'],
            ),
        ];
    }

    private function tool(string $name, string $description, array $properties, array $required): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $name,
                'description' => $description,
                'parameters' => [
                    'type' => 'object',
                    'properties' => $properties,
                    'required' => $required,
                    'additionalProperties' => false,
                ],
            ],
        ];
    }
}
