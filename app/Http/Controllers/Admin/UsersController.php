<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Notifications\AccountCreated;
use App\Support\Workspaces;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Features;

/**
 * UsersController
 *
 * Admin user management: the list (search, role tabs, status filter), invite, edit, password link and delete,
 * all from one page: modals for add / edit, a row menu for the rest (admin/users/index).
 */
class UsersController extends Controller
{
    /** Rows per page on the users table. */
    const PER_PAGE = 20;

    /**
     * The users table: search (?q=), role (?role=, `none` for no role) and status (?status=) filters. Unconfirmed
     * emails only matter where sign-ups confirm their address (config/fortify.php).
     */
    public function index(Request $request)
    {
        $roles = Role::orderBy('id')->get();
        $role = $roles->firstWhere('name', $request->query('role'));
        $noRole = $request->query('role') === 'none';
        $status = $request->query('status');
        $this->confirmsEmail = Features::enabled(Features::emailVerification());

        $this->users = User::with('role')
            ->when($request->query('q'), fn ($query, $q) => $query->where(fn ($query) => $query
                ->where('name', 'like', "%{$q}%")
                ->orWhere('email', 'like', "%{$q}%")))
            ->when($role, fn ($query) => $query->where('role_id', $role->id))
            ->when($noRole, fn ($query) => $query->whereNull('role_id'))
            ->when($status === 'active', fn ($query) => $query->whereNull('disabled_at'))
            ->when($status === 'disabled', fn ($query) => $query->whereNotNull('disabled_at'))
            ->when($status === 'unconfirmed' && $this->confirmsEmail, fn ($query) => $query->whereNull('email_verified_at'))
            ->orderBy('name')
            ->paginate(self::PER_PAGE)
            ->withQueryString();
        $this->roleFilter = $roles->pluck('label', 'name')->put('none', __('No role'))->all();
        $this->roleOptions = $roles->pluck('label', 'hash');
        $this->statuses = ['active' => __('Active'), 'disabled' => __('Disabled')]
            + ($this->confirmsEmail ? ['unconfirmed' => __('Unconfirmed email')] : []);

        return view('admin.users.index', $this->data);
    }

    /** New users get no password: the invite email links to the reset page so they choose their own. */
    public function store(Request $request)
    {
        $data = $this->validated($request);

        $user = DB::transaction(function () use ($data) {
            $user = User::forceCreate([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Str::random(40),
                'role_id' => $data['role_id'],
            ]);

            Workspaces::provision($user);

            return $user;
        });

        $user->notify(new AccountCreated(Password::broker()->createToken($user)));

        $this->redirect = url()->previous();

        return $this->jsonSuccess(__(':name added. An invite to choose a password is on its way.', ['name' => $user->name]));
    }

    /** Saves a user's name, email and role; admins can't change their own role. */
    public function update(Request $request, User $user)
    {
        $data = $this->validated($request, $user);

        if ($user->is($request->user()) && (int) $data['role_id'] !== $user->role_id) {
            return $this->jsonError(__("You can't change your own role"), 422);
        }

        $user->forceFill([
            'name' => $data['name'],
            'email' => $data['email'],
            'role_id' => $data['role_id'],
        ])->save();

        $this->redirect = url()->previous();

        return $this->jsonSuccess(__(':name updated', ['name' => $user->name]));
    }

    /** Emails the user a password reset link. */
    public function sendPasswordLink(User $user)
    {
        if ($user->isDisabled()) {
            return $this->jsonError(__('Enable :name first', ['name' => $user->name]), 422);
        }

        $status = Password::sendResetLink(['email' => $user->email]);

        if ($status !== Password::RESET_LINK_SENT) {
            return $this->jsonError(__($status), 429);
        }

        return $this->jsonSuccess(__('Password link sent to :email', ['email' => $user->email]));
    }

    /**
     * Disables a user, or enables them again (enable=1). Users are never deleted: a disabled account can't sign in
     * and is signed out on its next request (EnsureAccountIsActive), while its forms and data stay. Admins can't
     * disable themselves.
     */
    public function disable(Request $request, User $user)
    {
        return $this->setDisabled($request, collect([$user]));
    }

    /** Disables (or, with enable=1, enables) the ticked users: the list's bulk action. */
    public function disableMany(Request $request)
    {
        $data = $request->validate([
            'users' => ['required', 'array', 'max:100'],
            'users.*' => ['string'],
        ]);

        return $this->setDisabled($request, User::whereIn('hash', $data['users'])->get());
    }

    /**
     * Disables or enables the users, all or none.
     *
     * @param  Collection<int, User>  $users
     */
    private function setDisabled(Request $request, Collection $users)
    {
        $enable = $request->boolean('enable');
        if (! $enable && $users->contains(fn (User $user) => $user->is($request->user()))) {
            return $this->jsonError(__("You can't disable your own account"), 422);
        }

        User::whereKey($users->map->getKey())->update(['disabled_at' => $enable ? null : now()]);
        $this->redirect = url()->previous();

        if ($users->count() === 1) {
            return $this->jsonSuccess(__($enable ? ':name enabled' : ':name disabled', ['name' => $users->first()->name]));
        }

        return $this->jsonSuccess($enable
            ? trans_choice(':count user enabled|:count users enabled', $users->count())
            : trans_choice(':count user disabled|:count users disabled', $users->count()));
    }

    /**
     * Validation shared by add and edit. The page posts the role's hash (never its id); role_id is looked up from it.
     *
     * @return array{name: string, email: string, role: string, role_id: int}
     */
    private function validated(Request $request, ?User $user = null): array
    {
        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'role' => ['required', 'string', Rule::exists('roles', 'hash')],
        ], [
            'role.required' => __('Choose a role.'),
        ]);
        $data['role_id'] = Role::where('hash', $data['role'])->value('id');

        return $data;
    }

    /** Registers the users routes. */
    public static function routes(): void
    {
        Route::get('/', [self::class, 'index'])->name('users');
        Route::post('/', [self::class, 'store'])->name('users.store');
        Route::post('/{user}/update', [self::class, 'update'])->name('users.update');
        Route::post('/{user}/password-link', [self::class, 'sendPasswordLink'])->middleware('throttle:auth-email')->name('users.password-link');
        Route::post('/disable', [self::class, 'disableMany'])->name('users.disable-many');
        Route::post('/{user}/disable', [self::class, 'disable'])->name('users.disable');
    }
}
