<?php

namespace App\Support;

use App\Models\Form;
use App\Models\SubmissionView;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * ResponsesQuery
 *
 * The responses a page lists, from its query string: the active or archived ones (?archived=1), a saved view's filters
 * (?view=) or the filter panel's own (?filters=, base64url JSON, which change a view without saving it) and the search
 * (?q=). Shared by the responses table, its exports and the visualizer, so they always count the same responses.
 */
class ResponsesQuery
{
    /**
     * @param  list<array{name: string, title: string, type: string, kind: string, max: int|null, choices: array<string, string>}>  $questions
     * @param  array<string, array{title: string, kind: string, choices: array<string, string>}>  $fields
     * @param  array{from: ?string, to: ?string, rules: list<array{question: string, operator: string, value: mixed}>}  $filters
     */
    public function __construct(
        public readonly Form $form,
        public readonly bool $archived,
        public readonly array $questions,
        public readonly array $fields,
        public readonly ?SubmissionView $activeView,
        public readonly array $filters,
        public readonly ?string $search,
    ) {}

    /** The responses the request asks for. */
    public static function from(Request $request, Form $form): self
    {
        $questions = SurveyAnswers::questions($form->content);
        $fields = SubmissionFilters::fields($questions);
        $activeView = $request->filled('view') ? $form->views()->where('hash', $request->query('view'))->first() : null;
        $search = $request->query('q');

        return new self(
            form: $form,
            archived: $request->boolean('archived'),
            questions: $questions,
            fields: $fields,
            activeView: $activeView,
            filters: SubmissionFilters::normalise(self::requestedFilters($request) ?? $activeView?->filters, $fields),
            search: is_string($search) && $search !== '' ? $search : null,
        );
    }

    /** How many constraints the filters carry. */
    public function filterCount(): int
    {
        return SubmissionFilters::count($this->filters);
    }

    /** The responses, in no particular order. */
    public function query(): Builder
    {
        return $this->form->submissions()->getQuery()->{$this->archived ? 'archived' : 'active'}()
            ->tap(fn (Builder $query) => SubmissionFilters::apply($query, $this->filters))
            ->when($this->search, fn (Builder $query, string $q) => $query->where('data', 'like', '%'.addcslashes($q, '%_\\').'%'));
    }

    /**
     * The query string that brings the same responses back (view, filters, search, archived), for links between the
     * responses pages.
     *
     * @return array<string, string>
     */
    public static function carried(Request $request): array
    {
        return array_filter($request->only(['view', 'filters', 'q', 'archived']), fn ($value) => is_string($value) && $value !== '');
    }

    /** The panel's filters from ?filters= (base64url JSON), or null when there are none or they don't decode. */
    private static function requestedFilters(Request $request): ?array
    {
        $encoded = $request->query('filters');
        if (! is_string($encoded) || $encoded === '') {
            return null;
        }
        $decoded = json_decode((string) base64_decode(strtr($encoded, '-_', '+/'), true), true);

        return is_array($decoded) ? $decoded : null;
    }
}
