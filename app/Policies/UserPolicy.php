<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAbleTo('users-read');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, User $model): bool
    {
        return $user->isAbleTo('users-read') || $user->id === $model->id; 
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isAbleTo('users-create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $model): bool
    {
        return $user->isAbleTo('users-update') || $user->id === $model->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): bool
    {
        // Users with the users-delete permission could not delete another users whose hierarchy is lower.
        // The Superadmins are the only users who can delete users with the same hierarchy level.
        // Any user can delete himself.
        return ($user->isAbleTo('users-delete') && ($user->role()->hierarchy < $model->role()->hierarchy || $user->role()->hierarchy < 1))
            || $user->id === $model->id;
    }
}
