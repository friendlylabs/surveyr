<?php

namespace App\Policies;

use App\Enums\SpaceRole;
use App\Models\Form;
use App\Models\User;
use App\Policies\Concerns\ChecksWorkspaceRole;
use Illuminate\Auth\Access\Response;

/**
 * FormPolicy
 *
 * Who can see and change a form: its owner and collaborators, plus everyone with a role in one of its spaces (their
 * strongest role counts); the workspace's Owner and Admins can do everything. Anyone in the workspace can create one.
 */
class FormPolicy
{
    use ChecksWorkspaceRole;

    /** The forms list: everyone in the workspace (it shows the forms they can see). */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /** Opening a form and its responses. */
    public function view(User $user, Form $form): bool
    {
        return $this->managesWorkspace($user)
            || $this->ownsOrShares($user, $form)
            || $form->spaceRoleOf($user) !== null;
    }

    /** Creating a form: anyone in the workspace. */
    public function create(User $user): bool
    {
        return true;
    }

    /** Editing the form and its settings: owner, collaborators, and space Managers / Editors. */
    public function update(User $user, Form $form): bool
    {
        return $this->managesWorkspace($user)
            || $this->ownsOrShares($user, $form)
            || (bool) $form->spaceRoleOf($user)?->canEditForms();
    }

    /** Who the form is shared with and which spaces it sits in: owner and space Managers. */
    public function manage(User $user, Form $form): bool
    {
        return $this->managesWorkspace($user)
            || $form->user_id === $user->id
            || $form->spaceRoleOf($user) === SpaceRole::Manager;
    }

    /** Archiving the form (and restoring it): as for manage. */
    public function archive(User $user, Form $form): bool
    {
        return $this->manage($user, $form);
    }

    /** Deleting the form: whoever may archive it, and only while it has no data (one with data is archived). */
    public function delete(User $user, Form $form): Response
    {
        if (! $this->archive($user, $form)) {
            return Response::deny();
        }

        return $form->hasResponses()
            ? Response::deny(__(':title has responses; archive it instead', ['title' => $form->title]))
            : Response::allow();
    }

    /** Archiving responses out of the list, and restoring them: whoever may edit the form. */
    public function archiveResponses(User $user, Form $form): bool
    {
        return $this->update($user, $form);
    }

    /** Purging the form's responses (deleting every one for good): its owner and collaborators, and workspace managers. */
    public function purgeResponses(User $user, Form $form): bool
    {
        return $this->managesWorkspace($user) || $this->ownsOrShares($user, $form);
    }

    /** True for the form's owner or a collaborator. */
    private function ownsOrShares(User $user, Form $form): bool
    {
        return $form->user_id === $user->id || $form->collaborators()->whereKey($user->id)->exists();
    }
}
