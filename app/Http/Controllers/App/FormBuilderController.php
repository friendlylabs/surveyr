<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Support\FormTheme;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * FormBuilderController
 *
 * The form builder (SurveyJS Creator) and its autosave, plus a preview of the form as respondents see it. Editing
 * follows FormPolicy::update; anyone who can see the form can preview it.
 */
class FormBuilderController extends Controller
{
    /** The largest form definition the builder may save, in bytes of JSON. */
    const MAX_CONTENT_BYTES = 2_000_000;

    /** The largest image the builder may upload, in kilobytes. */
    const MAX_ASSET_KILOBYTES = 4096;

    /** The builder. */
    public function edit(Form $form): View
    {
        Gate::authorize('update', $form);

        $this->form = $form;

        return view('app.forms.build', $this->data);
    }

    /** The form as respondents see it; nothing is saved. */
    public function preview(Form $form): View
    {
        Gate::authorize('view', $form);

        $this->form = $form;

        return view('app.forms.preview', $this->data);
    }

    /**
     * Autosave: stores the builder's definition when the revision it loaded is still the latest, so one editor
     * can't silently overwrite another's changes (409 with the latest revision instead). The survey's title becomes
     * the form's title, so the list follows the builder.
     */
    public function save(Request $request, Form $form): JsonResponse
    {
        Gate::authorize('update', $form);

        $data = $request->validate([
            'content' => ['required', 'array'],
            'revision' => ['required', 'integer', 'min:0'],
        ]);

        if (strlen(json_encode($data['content'])) > self::MAX_CONTENT_BYTES) {
            return $this->jsonError(__('This form is too large to save.'), 422);
        }

        $saved = DB::transaction(function () use ($form, $data) {
            $current = Form::whereKey($form->getKey())->lockForUpdate()->firstOrFail();
            if ($current->revision !== $data['revision']) {
                return null;
            }

            $current->content = $data['content'];
            $current->title = $this->titleFrom($data['content']) ?? $current->title;
            $current->revision++;
            $current->save();

            return $current;
        });

        if (! $saved) {
            $this->revision = $form->fresh()->revision;

            return $this->jsonError(__('Someone else saved changes to this form. Reload to see them before you carry on.'), 409);
        }

        $this->revision = $saved->revision;
        $this->title = $saved->title;

        return $this->jsonSuccess(__('Saved'));
    }

    /** Saves the theme picked in the builder (FormTheme: style, layout, palette or colour, mode, font, background). */
    public function saveTheme(Request $request, Form $form): JsonResponse
    {
        Gate::authorize('update', $form);

        $form->theme = FormTheme::clean($request->validate(FormTheme::rules($form), [
            'backgroundImage.starts_with' => __('Upload the background image here.'),
            'color.regex' => __('Use a colour like #19b394.'),
        ]));
        $form->save();

        return $this->jsonSuccess(__('Theme saved'));
    }

    /**
     * Stores an image for the form (a logo, a background, a picture in a question) on the public disk, so it isn't
     * written into the form as text; answers with its URL. SVG is refused: it can carry scripts.
     */
    public function uploadAsset(Request $request, Form $form): JsonResponse
    {
        Gate::authorize('update', $form);

        $request->validate([
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,gif,webp', 'max:'.self::MAX_ASSET_KILOBYTES],
        ]);

        $path = $request->file('file')->store(FormTheme::assetDirectory($form), 'public');
        $this->url = FormTheme::assetUrl($form, basename($path));

        return $this->jsonSuccess(__('Image uploaded'));
    }

    /** The survey's own title (the default language's, for a translated one), or null when it has none. */
    private function titleFrom(array $content): ?string
    {
        $title = $content['title'] ?? null;
        if (is_array($title)) {
            $title = $title['default'] ?? reset($title);
        }

        return is_string($title) && trim($title) !== '' ? Str::limit(trim(strip_tags($title)), 255, '') : null;
    }

    /** Registers the builder routes (inside the forms prefix). */
    public static function routes(): void
    {
        Route::get('/{form}/build', [self::class, 'edit'])->name('forms.build');
        Route::post('/{form}/build', [self::class, 'save'])->name('forms.build.save');
        Route::get('/{form}/preview', [self::class, 'preview'])->name('forms.preview');
        Route::post('/{form}/theme', [self::class, 'saveTheme'])->name('forms.theme');
        Route::post('/{form}/assets', [self::class, 'uploadAsset'])->name('forms.assets');
    }
}
