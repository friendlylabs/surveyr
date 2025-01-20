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
use App\Models\Collection;
use App\Models\Space;

class DashboardController extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function home()
    {
        $this->stats = $this->homeStats();
        $this->statsChart = Collection::formStats(auth()->id());
        $this->recentSubmissions = Collection::recentSubmissions(auth()->id());

        return $this->renderPage("Dashboard", "app.dashboard.home");
    }

    # home stats
    protected function homeStats() : array
    {
        return [
            [   
                'title' => 'Forms',
                'subtitle' => 'All Forms',
                'count' => Form::userForms(auth()->id())->count(),
                'icon' => 'fa-regular fa-table-tree text-success'
            ],

            # open forms
            [
                'title' => 'Open',
                'subtitle' => 'Open for submission',
                'count' => Form::openForms(auth()->id())->count(),
                'icon' => 'fa-regular fa-seal-question text-warning'
            ],

            # spaces
            [
                'title' => 'Spaces',
                'subtitle' => 'All Spaces',
                'count' => Space::userSpaces(auth()->id())->count(),
                'icon' => 'fa-regular fa-folders text-info'
            ],

            # unreviewed
            [
                'title' => 'Pending',
                'subtitle' => 'unreviewed submissions',
                'count' => Collection::ofReview('pending', auth()->id())->count(),
                'icon' => 'fa-regular fa-seal-exclamation text-danger'
            ]

        ];
    }

    public static function routes() : void
    {
        app()::get('dashboard', ['name'=>'dashboard', 'DashboardController@index']);
    }
}