<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Models\SubmissionView;
use App\Support\SubmissionFilters;
use App\Support\SurveyAnswers;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

/**
 * FormOverviewController
 *
 * A form's overview, the first screen when a form is opened: its status and actions, then tabs (?tab=) for an Overview
 * (response stats and the last 30 days), its Submissions, and, for those who can edit it, its Settings.
 */
class FormOverviewController extends Controller
{
    /** Days in the daily responses chart. */
    const DAYS = 30;

    /** The overview. */
    public function show(Request $request, Form $form, FormSettingsController $settings): View
    {
        Gate::authorize('view', $form);

        $canEdit = Gate::allows('update', $form);
        $tabs = $canEdit ? ['overview', 'submissions', 'settings'] : ['overview', 'submissions'];

        $this->form = $form;
        $this->canEdit = $canEdit;
        $this->tab = in_array($request->query('tab'), $tabs, true) ? $request->query('tab') : 'overview';
        $this->stats = $this->stats($form);
        $this->daily = $this->daily($form);
        $this->views = $this->views($form);

        if ($canEdit) {
            $this->data += $settings->sectionData($request, $form);
        }

        return view('app.forms.show', $this->data);
    }

    /**
     * The form's saved views, each with how many (unarchived) responses it shows and its rules in words.
     *
     * @return Collection<int, SubmissionView>
     */
    private function views(Form $form): Collection
    {
        $fields = SubmissionFilters::fields(SurveyAnswers::questions($form->content));

        return $form->views()->with('user')->get()->each(function (SubmissionView $view) use ($form, $fields) {
            $filters = SubmissionFilters::normalise($view->filters, $fields);
            $view->matches = $form->submissions()->active()->tap(fn ($query) => SubmissionFilters::apply($query, $filters))->count();
            $view->summary = SubmissionFilters::describe($filters, $fields);
            $view->canChange = Gate::allows('delete', $view);
        });
    }

    /**
     * Response counts: in all, the last 7 days and the 7 before, and when the latest came in.
     *
     * @return array{total: int, last7: int, prev7: int, lastAt: ?CarbonImmutable}
     */
    private function stats(Form $form): array
    {
        $now = CarbonImmutable::now();
        $latest = $form->submissions()->active()->max('created_at');

        return [
            'total' => $form->submissions()->active()->count(),
            'last7' => $form->submissions()->active()->where('created_at', '>=', $now->subDays(7))->count(),
            'prev7' => $form->submissions()->active()->whereBetween('created_at', [$now->subDays(14), $now->subDays(7)])->count(),
            'lastAt' => $latest ? CarbonImmutable::parse($latest) : null,
        ];
    }

    /**
     * Responses per day for the last DAYS days, oldest first.
     *
     * @return list<array{date: CarbonImmutable, count: int}>
     */
    private function daily(Form $form): array
    {
        $start = CarbonImmutable::today()->subDays(self::DAYS - 1);
        $counts = $form->submissions()->active()
            ->where('created_at', '>=', $start)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $days = [];
        for ($i = 0; $i < self::DAYS; $i++) {
            $date = $start->addDays($i);
            $days[] = ['date' => $date, 'count' => (int) ($counts[$date->toDateString()] ?? 0)];
        }

        return $days;
    }

    /** Registers the overview route (inside the forms prefix). */
    public static function routes(): void
    {
        Route::get('/{form}', [self::class, 'show'])->name('forms.show');
    }
}
