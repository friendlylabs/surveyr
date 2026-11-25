<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * AuthLayout
 *
 * The auth pages' layout (layouts.auth).
 */
class AuthLayout extends Component
{
    /** The page title. */
    public ?string $title;

    /** Create the component. */
    public function __construct(?string $title = null)
    {
        $this->title = $title;
    }

    /**
     * Get the view / contents that represents the component.
     */
    public function render(): View
    {
        return view('layouts.auth');
    }
}
