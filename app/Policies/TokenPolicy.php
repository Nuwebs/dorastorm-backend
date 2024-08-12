<?php

namespace App\Policies;

use App\Models\Token;
use App\Models\User;
use App\Utils\DsPermission;
use Illuminate\Auth\Access\Response;

class TokenPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(DsPermission::MANAGE_API_KEYS_READ);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Token $token): bool
    {
        return $user->hasPermission(DsPermission::MANAGE_API_KEYS_READ) || (
            $user->hasPermission(DsPermission::API_KEYS_READ) && $token->user_id === $user->id
        );
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission(DsPermission::MANAGE_API_KEYS_CREATE) ||
            $user->hasPermission(DsPermission::API_KEYS_CREATE)
        ;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Token $token): bool
    {
        return $user->hasPermission(DsPermission::MANAGE_API_KEYS_UPDATE) || (
            $user->hasPermission(DsPermission::API_KEYS_UPDATE) && $token->user_id === $user->id
        );
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Token $token): bool
    {
        return $user->hasPermission(DsPermission::MANAGE_API_KEYS_DELETE) || (
            $user->hasPermission(DsPermission::API_KEYS_DELETE) && $token->user_id === $user->id
        );
    }
}
