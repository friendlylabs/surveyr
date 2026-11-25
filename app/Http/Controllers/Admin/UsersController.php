<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Notifications\AccountCreated;
use App\Support\Workspaces;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

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

    /** ?status= filter values and their labels. */
    const STATUSES = ['verified' => 'Confirmed', 'unverified' => 'Unconfirmed'];

    /** The users table: search (?q=), role tab (?role=, `none` for no role) and status (?status=) filters. */
    public function index(Request $request)
    {
        $roles = Role::orderBy('id')->get();
        $noRole = $request->query('role') === 'none';
        $role = $roles->firstWhere('name', $request->query('role'));
        $status = $request->query('status');

        $this->users = User::with('role')
            ->when($request->query('q'), fn ($query, $q) => $query->where(fn ($query) => $query
                ->where('name', 'like', "%{$q}%")
                ->orWhere('email', 'like', "%{$q}%")))
            ->when($role, fn ($query) => $query->where('role_id', $role->id))
            ->when($noRole, fn ($query) => $query->whereNull('role_id'))
            ->when($status === 'verified', fn ($query) => $query->whereNotNull('email_verified_at'))
            ->when($status === 'unverified', fn ($query) => $query->whereNull('email_verified_at'))
            ->orderBy('name')
            ->paginate(self::PER_PAGE)
            ->withQueryString();
        $this->roles = $roles;
        $this->role = $role;
        $this->noRole = $noRole;
        $this->roleCounts = User::selectRaw('role_id, count(*) as total')->groupBy('role_id')->pluck('total', 'role_id');
        $this->roleBadges = ['admin' => 'pc-badge-dark', 'member' => 'pc-badge-neutral'];
        $this->roleOptions = $roles->pluck('label', 'id');
        $this->statuses = self::STATUSES;

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

        return $this->jsonSuccess("{$user->name} added. An invite to choose a password is on its way.");
    }

    /** Saves a user's name, email and role; admins can't change their own role. */
    public function update(Request $request, User $user)
    {
        $data = $this->validated($request, $user);

        if ($user->is($request->user()) && (int) $data['role_id'] !== $user->role_id) {
            return $this->jsonError("You can't change your own role", 422);
        }

        $user->forceFill([
            'name' => $data['name'],
            'email' => $data['email'],
            'role_id' => $data['role_id'],
        ])->save();

        $this->redirect = url()->previous();

        return $this->jsonSuccess("{$user->name} updated");
    }

    /** Emails the user a password reset link. */
    public function sendPasswordLink(User $user)
    {
        $status = Password::sendResetLink(['email' => $user->email]);

        if ($status !== Password::RESET_LINK_SENT) {
            return $this->jsonError(__($status), 429);
        }

        return $this->jsonSuccess("Password link sent to {$user->email}");
    }

    /** Deletes a user; admins can't delete their own account. */
    public function destroy(Request $request, User $user)
    {
        if ($user->is($request->user())) {
            return $this->jsonError("You can't delete your own account", 422);
        }

        $user->delete();

        $this->redirect = url()->previous();

        return $this->jsonSuccess("{$user->name} deleted");
    }

    /** Validation shared by add and edit. */
    private function validated(Request $request, ?User $user = null): array
    {
        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
        ], [
            'role_id.required' => 'Choose a role.',
        ]);
    }

    /** Registers the users routes. */
    public static function routes(): void
    {
        Route::get('/', [self::class, 'index'])->name('users');
        Route::post('/', [self::class, 'store'])->name('users.store');
        Route::post('/{user}/update', [self::class, 'update'])->name('users.update');
        Route::post('/{user}/password-link', [self::class, 'sendPasswordLink'])->middleware('throttle:auth-email')->name('users.password-link');
        Route::post('/{user}/delete', [self::class, 'destroy'])->name('users.destroy');
    }
}
