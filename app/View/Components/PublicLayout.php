<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * PublicLayout
 *
 * The public pages' layout (layouts.public): pages anyone reaches without signing in, such as a form for respondents.
 * No app chrome; the page draws everything itself.
 */
class PublicLayout extends Component
{
    /** The page title (shown alone, without the app's name). */
    public ?string $title;

    /** Leave the page background clear, so a page embedding this one shows through. */
    public bool $clear;

    /** Keep the page out of search engines. */
    public bool $noindex;

    /** Follow the visitor's light / dark choice; off where the page brings its own colours (a themed form). */
    public bool $followTheme;

    /** Create the component. */
    public function __construct(?string $title = null, bool $clear = false, bool $noindex = true, bool $followTheme = true)
    {
        $this->followTheme = $followTheme;
        $this->title = $title;
        $this->clear = $clear;
        $this->noindex = $noindex;
    }

    /**
     * Get the view / contents that represents the component.
     */
    public function render(): View
    {
        return view('layouts.public');
    }
}
