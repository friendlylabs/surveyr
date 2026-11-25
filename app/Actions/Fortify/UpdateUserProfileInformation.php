<?php

namespace App\Actions\Fortify;

use App\Models\User;
use App\Support\Locales;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;
use Laravel\Fortify\Features;

/**
 * UpdateUserProfileInformation
 *
 * Fortify's profile update: validates and saves the user's name, email and interface language.
 */
class UpdateUserProfileInformation implements UpdatesUserProfileInformation
{
    /**
     * Validate and update the given user's profile information.
     *
     * @param  array<string, string>  $input
     *
     * @throws ValidationException
     */
    public function update(User $user, array $input): void
    {
        $input['email'] = Str::lower(trim((string) ($input['email'] ?? '')));

        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],

            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],

            'locale' => ['nullable', 'string', Rule::in(array_keys(Locales::supported()))],
        ])->validateWithBag('updateProfileInformation');

        // left out of the request, the language stays as it is; empty follows the workspace default
        $input['locale'] = array_key_exists('locale', $input) ? ($input['locale'] ?: null) : $user->locale;

        // a changed address is unconfirmed again, while email verification is on
        if ($input['email'] !== $user->email &&
            $user instanceof MustVerifyEmail &&
            Features::enabled(Features::emailVerification())) {
            $this->updateVerifiedUser($user, $input);
        } else {
            $user->forceFill([
                'name' => $input['name'],
                'email' => $input['email'],
                'locale' => $input['locale'],
            ])->save();
        }
    }

    /**
     * Update the given verified user's profile information.
     *
     * @param  array<string, string>  $input
     */
    protected function updateVerifiedUser(User $user, array $input): void
    {
        $user->forceFill([
            'name' => $input['name'],
            'email' => $input['email'],
            'locale' => $input['locale'],
            'email_verified_at' => null,
        ])->save();

        $user->sendEmailVerificationNotification();
    }
}
