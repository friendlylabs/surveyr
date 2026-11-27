<?php

namespace App\Http\Controllers\App;

use App\Enums\SpaceRole;
use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Models\Space;
use App\Models\User;
use App\Policies\Concerns\ChecksWorkspaceRole;
use App\Support\CurrentWorkspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * SpacesController
 *
 * Spaces (projects) in the current workspace: the list, create / edit / delete, and a space's page with its members
 * (add, change role, remove) and its forms. Access follows SpacePolicy.
 */
class SpacesController extends Controller
{
    use ChecksWorkspaceRole;

    /** Rows per page on the spaces list. */
    const PER_PAGE = 20;

    /** The spaces the user is in (every space, for the workspace's managers), with member and form counts. */
    public function index(Request $request): View
    {
        $user = $request->user();

        $visible = Space::query()->unless($this->managesWorkspace($user), fn ($query) => $query->whereHas('members', fn ($query) => $query->whereKey($user->id)));
        // nothing to filter yet: the list hides its search
        $this->hasSpaces = (clone $visible)->exists();

        $this->spaces = $visible
            ->with(['members' => fn ($query) => $query->whereKey($user->id)])
            ->withCount(['members', 'forms'])
            ->when($request->query('q'), fn ($query, $q) => $query->where('name', 'like', "%{$q}%"))
            ->orderBy('name')
            ->paginate(self::PER_PAGE)
            ->withQueryString();
        $this->colors = Space::COLORS;

        return view('app.spaces.index', $this->data);
    }

    /** Starts a space; its creator becomes its Manager. */
    public function store(Request $request): JsonResponse
    {
        Gate::authorize('create', Space::class);
        $data = $this->validated($request);

        $space = DB::transaction(function () use ($data, $request) {
            $space = new Space($data);
            $space->user_id = $request->user()->id;
            $space->save();
            $space->addMember($request->user(), SpaceRole::Manager);

            return $space;
        });

        $this->redirect = route('spaces.show', $space);

        return $this->jsonSuccess(__(':name created', ['name' => $space->name]));
    }

    /** A space's page: its members and its forms. */
    public function show(Request $request, Space $space): View
    {
        Gate::authorize('view', $space);

        $space->load(['members' => fn ($query) => $query->orderBy('name')]);
        $this->space = $space;
        // the Forms tab is the same list as the forms page (app.forms.partials.table), for this space's forms
        $this->formsCount = $space->forms()->count();
        $this->hasForms = $this->formsCount > 0;
        $this->forms = $space->forms()
            ->with(['owner', 'spaces'])
            ->withCount(['submissions' => fn (Builder $query) => $query->active()])
            ->filtered($request->query('q'), $request->query('status'))
            ->latest('updated_at')
            ->paginate(FormsController::PER_PAGE)
            ->withQueryString();
        $this->statuses = Form::statusOptions();
        $this->canManage = Gate::allows('update', $space);
        // Forms, Members and (for managers) Settings are tabs; ?tab= opens one (pc-tabs.js keeps it in the url)
        $tabs = $this->canManage ? ['forms', 'members', 'settings'] : ['forms', 'members'];
        $this->tab = in_array($request->query('tab'), $tabs, true) ? $request->query('tab') : 'forms';
        $this->roles = SpaceRole::options();
        $this->candidates = $this->canManage
            ? app(CurrentWorkspace::class)->get()->members()->whereNotIn('users.id', $space->members->modelKeys())->orderBy('name')->pluck('name', 'users.hash')
            : collect();
        $this->colors = Space::COLORS;

        return view('app.spaces.show', $this->data);
    }

    /** Renames the space or changes its description and colour. */
    public function update(Request $request, Space $space): JsonResponse
    {
        Gate::authorize('update', $space);
        $space->update($this->validated($request));
        $this->redirect = url()->previous();

        return $this->jsonSuccess(__(':name updated', ['name' => $space->name]));
    }

    /** Deletes the space; its forms stay, outside it. */
    public function destroy(Space $space): JsonResponse
    {
        Gate::authorize('delete', $space);
        $space->delete();
        $this->redirect = route('spaces');

        return $this->jsonSuccess(__(':name deleted', ['name' => $space->name]));
    }

    /** Deletes the ticked spaces (the list's bulk action), all or none; their forms stay, outside them. */
    public function destroyMany(Request $request): JsonResponse
    {
        $data = $request->validate([
            'spaces' => ['required', 'array', 'max:100'],
            'spaces.*' => ['string'],
        ]);

        $spaces = Space::whereIn('hash', $data['spaces'])->get();
        foreach ($spaces as $space) {
            Gate::authorize('delete', $space);
        }

        DB::transaction(fn () => $spaces->each->delete());
        $this->redirect = url()->previous();

        return $this->jsonSuccess(trans_choice(':count space deleted|:count spaces deleted', $spaces->count()));
    }

    /** Adds a workspace member to the space in a role. */
    public function addMember(Request $request, Space $space): JsonResponse
    {
        Gate::authorize('update', $space);
        $data = $request->validate([
            'user' => ['required', 'string'],
            'role' => ['required', Rule::enum(SpaceRole::class)],
        ], ['user.required' => __('Choose someone to add.')]);

        $user = app(CurrentWorkspace::class)->get()->members()->where('users.hash', $data['user'])->first();
        if (! $user) {
            return $this->jsonError(__('That person isn\'t in this workspace.'), 422);
        }

        $space->addMember($user, SpaceRole::from($data['role']));
        $this->redirect = url()->previous();

        return $this->jsonSuccess(__(':name added to :space', ['name' => $user->name, 'space' => $space->name]));
    }

    /** Changes a member's role: not the creator's, not your own, and a space always keeps at least one Manager. */
    public function updateMember(Request $request, Space $space, User $member): JsonResponse
    {
        Gate::authorize('update', $space);
        abort_if($space->roleOf($member) === null, 404);
        if ($reason = $space->roleLockedFor($member, $request->user())) {
            return $this->jsonError($reason, 422);
        }
        $role = SpaceRole::from($request->validate(['role' => ['required', Rule::enum(SpaceRole::class)]])['role']);

        if ($role !== SpaceRole::Manager && $this->isLastManager($space, $member)) {
            return $this->jsonError(__('A space needs at least one Manager.'), 422);
        }

        $space->addMember($member, $role);
        $this->redirect = url()->previous();

        return $this->jsonSuccess(__(':name is now :role', ['name' => $member->name, 'role' => $role->label()]));
    }

    /** Removes a member from the space; not its creator, not yourself, and never the last Manager. */
    public function removeMember(Request $request, Space $space, User $member): JsonResponse
    {
        Gate::authorize('update', $space);
        if ($reason = $space->roleLockedFor($member, $request->user())) {
            return $this->jsonError($reason, 422);
        }

        if ($this->isLastManager($space, $member)) {
            return $this->jsonError(__('A space needs at least one Manager.'), 422);
        }

        $space->members()->detach($member->id);
        $this->redirect = url()->previous();

        return $this->jsonSuccess(__(':name removed from :space', ['name' => $member->name, 'space' => $space->name]));
    }

    /** True when the member is the space's only Manager. */
    private function isLastManager(Space $space, User $member): bool
    {
        $managers = $space->members()->wherePivot('role', SpaceRole::Manager->value)->pluck('users.id');

        return $managers->count() === 1 && $managers->first() === $member->id;
    }

    /**
     * Validation shared by create and edit.
     *
     * @return array{name: string, description: ?string, color: ?string}
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'color' => ['nullable', Rule::in(Space::COLORS)],
        ]);
    }

    /** Registers the spaces routes. */
    public static function routes(): void
    {
        Route::get('/', [self::class, 'index'])->name('spaces');
        Route::post('/', [self::class, 'store'])->name('spaces.store');
        Route::post('/delete', [self::class, 'destroyMany'])->name('spaces.destroy-many');
        Route::get('/{space}', [self::class, 'show'])->name('spaces.show');
        Route::post('/{space}/update', [self::class, 'update'])->name('spaces.update');
        Route::post('/{space}/delete', [self::class, 'destroy'])->name('spaces.destroy');
        Route::post('/{space}/members', [self::class, 'addMember'])->name('spaces.members.store');
        Route::post('/{space}/members/{member}/update', [self::class, 'updateMember'])->name('spaces.members.update');
        Route::post('/{space}/members/{member}/delete', [self::class, 'removeMember'])->name('spaces.members.destroy');
    }
}
