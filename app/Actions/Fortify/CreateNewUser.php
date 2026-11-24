<?php

namespace App\Actions\Fortify;

use App\Models\Role;
use App\Models\User;
use App\Support\Registration;
use App\Support\Workspaces;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use RuntimeException;

// public sign-up (AuthController::store), by config/auth.php `registration` (App\Support\Registration): the email must
// be on an allowed domain when a list is set, and new accounts get the default role, never admin
class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     *
     * @throws ValidationException
     */
    public function create(array $input): User
    {
        $input['email'] = Str::lower(trim((string) ($input['email'] ?? '')));

        $domains = Registration::allowedDomains();

        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'string', 'email', 'max:255', Rule::unique(User::class),
                function (string $attribute, string $value, Closure $fail) use ($domains) {
                    if (! Registration::allowsEmail($value)) {
                        $fail(count($domains) === 1
                            ? "Sign up with your @{$domains[0]} email address."
                            : 'Sign up with an email address on '.collect($domains)->map(fn ($d) => "@{$d}")->join(', ', ' or ').'.');
                    }
                },
            ],
            'password' => $this->passwordRules(),
        ])->validate();

        // the account and its workspace (Workspaces::provision) land together or not at all
        return DB::transaction(function () use ($input) {
            $user = User::forceCreate([
                'name' => $input['name'],
                'email' => $input['email'],
                'password' => $input['password'],
                'role_id' => $this->defaultRole()->id,
            ]);

            Workspaces::provision($user);

            return $user;
        });
    }

    // auth.registration.default_role, created on first use so a renamed default works without a seeder run;
    // never admin, so a config slip can't hand out full access to anyone who signs up
    private function defaultRole(): Role
    {
        $name = Registration::defaultRole();

        if ($name === 'admin') {
            throw new RuntimeException('auth.registration.default_role must not be "admin": anyone who signs up would get full access.');
        }

        return Role::firstOrCreate(['name' => $name]);
    }
}
