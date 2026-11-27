<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Models\Space;
use App\Models\User;
use App\Policies\Concerns\ChecksWorkspaceRole;
use App\Support\CurrentWorkspace;
use App\Support\DateFormat;
use App\Support\Webhooks;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;

/**
 * FormSettingsController
 *
 * A form's settings in three sections (the overview's Settings tab), each saved on its own: General (title, description, and for those who manage the
 * form its spaces and collaborators), Access (when it opens and closes, an access code) and Submission (where
 * respondents go afterwards, a webhook). Editors change them (FormPolicy::update); sharing needs FormPolicy::manage.
 */
class FormSettingsController extends Controller
{
    use ChecksWorkspaceRole;

    /** The old settings address: the settings live in the form overview's Settings tab now. */
    public function edit(Form $form): RedirectResponse
    {
        Gate::authorize('update', $form);

        return redirect()->route('forms.show', [$form, 'tab' => 'settings']);
    }

    /**
     * What the settings sections need (the overview's Settings tab, app.forms.partials.settings.*): whether the user
     * manages the form, and the spaces and people it can be shared with.
     *
     * @return array{canManage: bool, spaceOptions: array<string, string>, collaboratorOptions: array<string, string>}
     */
    public function sectionData(Request $request, Form $form): array
    {
        $form->loadMissing(['spaces', 'collaborators']);
        $canManage = Gate::allows('manage', $form);

        return [
            'canManage' => $canManage,
            'spaceOptions' => $canManage ? $this->spaceOptions($request, $form)->pluck('name', 'hash')->all() : [],
            'collaboratorOptions' => $canManage ? $this->members($form)->pluck('name', 'hash')->all() : [],
        ];
    }

    /**
     * General: title and description; with manage, its spaces and collaborators too. A space the user may not add
     * forms to keeps the form whatever is posted, so nobody takes a form out of a space they don't run.
     */
    public function updateGeneral(Request $request, Form $form): JsonResponse
    {
        Gate::authorize('update', $form);
        $canManage = Gate::allows('manage', $form);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'spaces' => ['array'],
            'spaces.*' => ['string'],
            'collaborators' => ['array'],
            'collaborators.*' => ['string'],
        ]);

        DB::transaction(function () use ($request, $form, $data, $canManage) {
            $form->title = $data['title'];
            $form->description = $data['description'] ?? null;
            // the builder takes the survey's title as the form's, so the two change together
            $this->changeContent($form, fn (array $content) => $this->withTitle($content, $data['title']));
            $form->save();

            if (! $canManage) {
                return;
            }

            $choosable = $this->spaceOptions($request, $form)->filter(fn (Space $space) => Gate::allows('addForms', $space));
            $chosen = $choosable->whereIn('hash', $data['spaces'] ?? [])->modelKeys();
            $kept = $form->spaces->reject(fn (Space $space) => $choosable->contains($space))->modelKeys();
            $form->spaces()->sync([...$chosen, ...$kept]);

            $form->collaborators()->sync($this->members($form)->whereIn('hash', $data['collaborators'] ?? [])->modelKeys());
        });

        $this->redirect = url()->previous();

        return $this->jsonSuccess(__('Settings saved'));
    }

    /** Access: when the form opens and closes (empty for no limit) and an access code respondents must enter. */
    public function updateAccess(Request $request, Form $form): JsonResponse
    {
        Gate::authorize('update', $form);

        $format = 'date_format:Y-m-d H:i';
        $data = $request->validate([
            'opens_at' => ['nullable', $format],
            'closes_at' => ['nullable', $format],
            'access_code' => ['nullable', 'string', 'min:4', 'max:64'],
        ], [
            'access_code.min' => __('Use at least 4 characters, or leave it empty for open access.'),
        ]);

        $opensAt = DateFormat::parse($data['opens_at'] ?? null, 'datetime');
        $closesAt = DateFormat::parse($data['closes_at'] ?? null, 'datetime');
        if ($opensAt && $closesAt && $closesAt->lte($opensAt)) {
            throw ValidationException::withMessages(['closes_at' => __('Close it after it opens.')]);
        }

        $form->opens_at = $opensAt;
        $form->closes_at = $closesAt;
        $form->access_code = filled($data['access_code'] ?? null) ? $data['access_code'] : null;
        $form->save();

        $this->redirect = url()->previous();

        return $this->jsonSuccess(__('Settings saved'));
    }

    /** Submission: a page to send respondents to once they finish, and a webhook told about each response. */
    public function updateSubmission(Request $request, Form $form): JsonResponse
    {
        Gate::authorize('update', $form);

        $data = $request->validate([
            'redirect_url' => ['nullable', 'url:http,https', 'max:2048'],
            'webhook_url' => ['nullable', 'url:http,https', 'max:2048'],
        ]);

        DB::transaction(function () use ($form, $data) {
            // SurveyJS sends respondents to the survey's navigateToUrl when they complete it
            $this->changeContent($form, function (array $content) use ($data) {
                if (filled($data['redirect_url'] ?? null)) {
                    $content['navigateToUrl'] = $data['redirect_url'];
                } else {
                    unset($content['navigateToUrl']);
                }

                return $content;
            });
            $form->webhook_url = $data['webhook_url'] ?? null;
            // a webhook signs its requests; it gets its secret when it's first set and keeps it
            if ($form->webhook_url && ! $form->webhook_secret) {
                $form->webhook_secret = Webhooks::secret();
            }
            $form->save();
        });

        $this->redirect = url()->previous();

        return $this->jsonSuccess(__('Settings saved'));
    }

    /**
     * Changes the form's SurveyJS definition and counts it as a revision when it really changed, so a builder left
     * open elsewhere reloads instead of saving over it (FormBuilderController::save).
     *
     * @param  Closure(array): array  $change
     */
    private function changeContent(Form $form, Closure $change): void
    {
        $before = $form->content ?? [];
        $after = $change($before);

        if ($after !== $before) {
            $form->content = $after;
            $form->revision++;
        }
    }

    /** The survey definition with its title set (the default language's, for a translated title). */
    private function withTitle(array $content, string $title): array
    {
        if (is_array($content['title'] ?? null)) {
            $content['title']['default'] = $title;
        } else {
            $content['title'] = $title;
        }

        return $content;
    }

    /**
     * The spaces the form could sit in for this user: those they're in (every space, for the workspace's managers),
     * plus the ones it already sits in.
     *
     * @return Collection<int, Space>
     */
    private function spaceOptions(Request $request, Form $form): Collection
    {
        $user = $request->user();

        return Space::query()
            ->where(fn (Builder $query) => $query
                ->unless($this->managesWorkspace($user), fn (Builder $query) => $query->whereHas('members', fn (Builder $query) => $query->whereKey($user->id)))
                ->orWhereIn('spaces.id', $form->spaces()->select('spaces.id')))
            ->orderBy('name')
            ->get();
    }

    /**
     * The workspace's people the form can be shared with: everyone but its owner.
     *
     * @return Collection<int, User>
     */
    private function members(Form $form): Collection
    {
        return app(CurrentWorkspace::class)->get()->members()
            ->when($form->user_id, fn ($query) => $query->whereKeyNot($form->user_id))
            ->orderBy('name')
            ->get();
    }

    /** Registers the settings routes (inside the forms prefix). */
    public static function routes(): void
    {
        Route::get('/{form}/settings', [self::class, 'edit'])->name('forms.settings');
        Route::post('/{form}/settings/general', [self::class, 'updateGeneral'])->name('forms.settings.general');
        Route::post('/{form}/settings/access', [self::class, 'updateAccess'])->name('forms.settings.access');
        Route::post('/{form}/settings/submission', [self::class, 'updateSubmission'])->name('forms.settings.submission');
    }
}
