<?php

namespace App\Policies;

use App\Models\SubmissionView;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

/**
 * SubmissionViewPolicy
 *
 * A form's saved views: anyone who can see the form uses them and saves new ones; the one who saved a view, and
 * whoever manages the form (FormPolicy@manage), changes or deletes it.
 */
class SubmissionViewPolicy
{
    /** Changing a view's name or rules. */
    public function update(User $user, SubmissionView $view): bool
    {
        return $view->user_id === $user->id || Gate::forUser($user)->allows('manage', $view->form);
    }

    /** Deleting a view (its responses are untouched). */
    public function delete(User $user, SubmissionView $view): bool
    {
        return $this->update($user, $view);
    }
}
