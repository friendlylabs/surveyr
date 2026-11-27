<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Models\Space;
use App\Policies\Concerns\ChecksWorkspaceRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * FormsController
 *
 * The forms list (search and status filters), creating a form (title, description, spaces) and deleting one.
 * Users see their own forms, those shared with them and those in their spaces; the workspace's managers see all.
 */
class FormsController extends Controller
{
    use ChecksWorkspaceRole;

    /** Rows per page on the forms list. */
    const PER_PAGE = 20;

    /** The forms the user can see. */
    public function index(Request $request): View
    {
        $user = $request->user();
        $status = $request->query('status');

        $visible = Form::query()->unless($this->managesWorkspace($user), fn (Builder $query) => $query->visibleTo($user));
        // nothing to filter yet: the list hides its search and filters
        $this->hasForms = (clone $visible)->exists();

        $this->forms = $visible
            ->with(['owner', 'spaces'])
            ->withCount(['submissions' => fn (Builder $query) => $query->active()])
            ->filtered($request->query('q'), $status)
            ->latest('updated_at')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        $this->statuses = Form::statusOptions();
        $this->addableSpaces = $this->visibleSpaces($request)->filter(fn (Space $space) => Gate::allows('addForms', $space))->pluck('name', 'hash');

        return view('app.forms.index', $this->data);
    }

    /** Creates a form owned by the user, in the chosen spaces (only ones they may add forms to). */
    public function store(Request $request): JsonResponse
    {
        Gate::authorize('create', Form::class);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'spaces' => ['array'],
            'spaces.*' => ['string', Rule::exists('spaces', 'hash')],
        ]);

        $spaces = Space::whereIn('hash', $data['spaces'] ?? [])->get();
        foreach ($spaces as $space) {
            Gate::authorize('addForms', $space);
        }

        $form = DB::transaction(function () use ($data, $spaces, $request) {
            $form = new Form([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'content' => ['title' => $data['title'], 'pages' => [['name' => 'page1', 'elements' => []]]],
            ]);
            $form->user_id = $request->user()->id;
            $form->save();
            $form->spaces()->sync($spaces->modelKeys());

            return $form;
        });

        // a new form opens on its overview, as in Surveyr
        $this->redirect = route('forms.show', $form);

        return $this->jsonSuccess(__(':title created', ['title' => $form->title]));
    }

    /** Archives the form: out of the list and no longer collecting; its data stays. */
    public function archive(Form $form): JsonResponse
    {
        Gate::authorize('archive', $form);
        $form->forceFill(['archived_at' => now()])->save();
        $this->redirect = url()->previous();

        return $this->jsonSuccess(__(':title archived', ['title' => $form->title]));
    }

    /** Brings an archived form back into the list. */
    public function restore(Form $form): JsonResponse
    {
        Gate::authorize('archive', $form);
        $form->forceFill(['archived_at' => null])->save();
        $this->redirect = url()->previous();

        return $this->jsonSuccess(__(':title restored', ['title' => $form->title]));
    }

    /** Archives the ticked forms, or restores them (restore=1); the list's bulk action. */
    public function archiveMany(Request $request): JsonResponse
    {
        $forms = $this->ticked($request, 'archive');
        $restore = $request->boolean('restore');

        Form::whereKey($forms->modelKeys())->update(['archived_at' => $restore ? null : now()]);
        $this->redirect = url()->previous();

        return $this->jsonSuccess($restore
            ? trans_choice(':count form restored|:count forms restored', $forms->count())
            : trans_choice(':count form archived|:count forms archived', $forms->count()));
    }

    /** Deletes a form that has no data; one with data is archived instead (FormPolicy::delete). */
    public function destroy(Form $form): JsonResponse
    {
        Gate::authorize('delete', $form);
        $form->delete();
        $this->redirect = url()->previous();

        return $this->jsonSuccess(__(':title deleted', ['title' => $form->title]));
    }

    /** Deletes the ticked forms (the list's bulk action); all or none, and only ones without data. */
    public function destroyMany(Request $request): JsonResponse
    {
        $forms = $this->ticked($request, 'delete');

        DB::transaction(fn () => $forms->each->delete());
        $this->redirect = url()->previous();

        return $this->jsonSuccess(trans_choice(':count form deleted|:count forms deleted', $forms->count()));
    }

    /**
     * The ticked forms (forms[] hashes) of a bulk action, each authorized for `ability`; one refusal stops them all.
     *
     * @return EloquentCollection<int, Form>
     */
    private function ticked(Request $request, string $ability): EloquentCollection
    {
        $data = $request->validate([
            'forms' => ['required', 'array', 'max:100'],
            'forms.*' => ['string'],
        ]);

        $forms = Form::whereIn('hash', $data['forms'])->get();
        foreach ($forms as $form) {
            Gate::authorize($ability, $form);
        }

        return $forms;
    }

    /**
     * The spaces the user is in (every space, for the workspace's managers), by name.
     *
     * @return Collection<int, Space>
     */
    private function visibleSpaces(Request $request)
    {
        $user = $request->user();

        return Space::query()
            ->unless($this->managesWorkspace($user), fn (Builder $query) => $query->whereHas('members', fn (Builder $query) => $query->whereKey($user->id)))
            ->orderBy('name')
            ->get();
    }

    /** Registers the forms routes. */
    public static function routes(): void
    {
        Route::get('/', [self::class, 'index'])->name('forms');
        Route::post('/', [self::class, 'store'])->name('forms.store');
        Route::post('/delete', [self::class, 'destroyMany'])->name('forms.destroy-many');
        Route::post('/archive', [self::class, 'archiveMany'])->name('forms.archive-many');
        Route::post('/{form}/archive', [self::class, 'archive'])->name('forms.archive');
        Route::post('/{form}/restore', [self::class, 'restore'])->name('forms.restore');
        Route::post('/{form}/delete', [self::class, 'destroy'])->name('forms.destroy');
    }
}
