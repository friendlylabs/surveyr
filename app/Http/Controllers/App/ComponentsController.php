<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Route;

/**
 * ComponentsController
 *
 * A live reference of the design system: every pc-* component and Blade component with the markup that renders it.
 * Delete this controller, its route (routes/app.php), view and sidebar item once the team knows the kit.
 */
class ComponentsController extends Controller
{
    /** The components page, with sample options for the select and choice components. */
    public function index()
    {
        $this->options = ['design' => 'Design', 'engineering' => 'Engineering', 'finance' => 'Finance', 'marketing' => 'Marketing', 'operations' => 'Operations', 'sales' => 'Sales', 'support' => 'Support', 'legal' => 'Legal'];

        return view('app.components.index', $this->data);
    }

    /** Registers the components route. */
    public static function routes(): void
    {
        Route::get('/', [self::class, 'index'])->name('components');
    }
}
