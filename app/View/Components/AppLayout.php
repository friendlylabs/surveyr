<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * AppLayout
 *
 * The signed-in app's page layout (layouts.app).
 */
class AppLayout extends Component
{
    /** The page title. */
    public ?string $title;

    /** A line of muted text under the title. */
    public ?string $subtitle;

    /** Determine if the sidebar should be collapsed (false) or expanded (true). */
    public bool $sidebar;

    /** Determine if the layout should be contained (true) or full-width (false). */
    public bool $contained;

    /** Determine if the layout should be visible only on desktop (true) or on all devices (false). */
    public bool $desktopOnly;

    /** Create the component. */
    public function __construct(?string $title = null, bool $sidebar = true, bool $containedLayout = true, bool $desktopOnly = false, ?string $subtitle = null)
    {
        $this->title = $title;
        $this->subtitle = $subtitle;
        $this->sidebar = $sidebar;
        $this->contained = $containedLayout;
        $this->desktopOnly = $desktopOnly;
    }

    /**
     * Get the view / contents that represents the component.
     */
    public function render(): View
    {
        return view('layouts.app');
    }
}
