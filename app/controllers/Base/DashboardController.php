<?php

/**
 * ----------------------------------------------------------
 * Dashboard Controller
 * ----------------------------------------------------------
 *
 * This controller is responsible for handling the dashboard
 * page and its related functionalities.
 *
 */

namespace App\Controllers\Base;
use App\Controllers\Controller;

use App\Models\Form;
use App\Models\Zone;
use App\Models\Space;
use App\Models\Report;
use App\Models\Collection;
use Leaf\Database as DB;

class DashboardController extends Controller
{
    /** days of daily submission counts handed to the chart (it re-buckets by range) */
    const SERIES_DAYS = 365;

    public function __construct()
    {
        parent::__construct();
    }

    public function home()
    {
        $userId = (int) auth()->id();
        $formIds = Form::accessibleIds($userId);

        $this->submissions = $this->submissionSummary($formIds);
        $this->stats = $this->homeStats($userId, $formIds);
        $this->series = $this->submissionSeries($formIds);
        $this->activeForms = $this->activeForms($formIds);

        return $this->renderPage("Dashboard", "app.dashboard.home");
    }

    /**
     * Submission totals across every form the user can reach, with the last
     * 30 days against the 30 before them.
     */
    protected function submissionSummary(array $formIds) : array
    {
        $base = fn () => Collection::whereIn('form_id', $formIds ?: [0]);

        $recent = $base()->where('created_at', '>=', now()->subDays(30))->count();
        $previous = $base()
            ->where('created_at', '>=', now()->subDays(60))
            ->where('created_at', '<', now()->subDays(30))
            ->count();

        return [
            'total' => $base()->count(),
            'recent' => $recent,
            'previous' => $previous,
            'delta' => $recent - $previous,
        ];
    }

    /**
     * Headline counts for each part of the platform.
     */
    protected function homeStats(int $userId, array $formIds) : array
    {
        return [
            [
                'title' => 'Forms',
                'count' => count($formIds),
                'icon' => 'ph-rows',
                'tone' => 'primary',
                'route' => route('forms.list'),
            ],
            [
                'title' => 'Spaces',
                'count' => Space::userSpaces($userId)->count(),
                'icon' => 'ph-folders',
                'tone' => 'info',
                'route' => route('spaces.list'),
            ],
            [
                'title' => 'Zones',
                'count' => Zone::count(),
                'icon' => 'ph-database',
                'tone' => 'warning',
                'route' => route('zones.list'),
            ],
            [
                'title' => 'Reports',
                'count' => Report::accessibleBy($userId)->count(),
                'icon' => 'ph-chart-bar',
                'tone' => 'success',
                'route' => route('reports.list'),
            ],
        ];
    }

    /**
     * The forms with the most submissions, with their owner, schedule and
     * how much of the last 30 days' traffic they took.
     */
    protected function activeForms(array $formIds, int $limit = 6) : object
    {
        $rows = Collection::whereIn('form_id', $formIds ?: [0])
            ->select(
                'form_id',
                DB::$capsule::raw('COUNT(id) as submission_count'),
                DB::$capsule::raw('SUM(created_at >= "' . now()->subDays(30)->toDateTimeString() . '") as recent_count'),
                DB::$capsule::raw('MAX(created_at) as latest_submission'))
            ->groupBy('form_id')
            ->orderByDesc('submission_count')
            ->limit($limit)
            ->get();

        $forms = Form::with('user')
            ->whereIn('id', $rows->pluck('form_id'))
            ->get(['id', 'title', 'slug', 'user_id', 'start_date', 'end_date', 'is_indefinite'])
            ->keyBy('id');

        return $rows->map(function ($row) use ($forms) {
            $row->form = $forms->get($row->form_id);
            $row->recent_count = (int) $row->recent_count;
            return $row;
        })->filter(fn ($row) => $row->form)->values();
    }

    /**
     * Submissions per day for the last year: { 'YYYY-MM-DD': n }. Days with
     * no submissions are absent; the chart fills them in.
     */
    protected function submissionSeries(array $formIds) : array
    {
        return Collection::whereIn('form_id', $formIds ?: [0])
            ->where('created_at', '>=', now()->subDays(self::SERIES_DAYS)->startOfDay())
            ->select(DB::$capsule::raw('DATE(created_at) as day'), DB::$capsule::raw('COUNT(id) as n'))
            ->groupBy('day')
            ->pluck('n', 'day')
            ->map(fn ($n) => (int) $n)
            ->all();
    }

    public static function routes() : void
    {
        app()::get('dashboard', ['name'=>'dashboard', 'DashboardController@index']);
    }
}
