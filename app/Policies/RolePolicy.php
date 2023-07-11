<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;
use App\Utils\DsPermission;

class RolePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAbleTo(DsPermission::ROLES_READ);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Role $role): bool
    {
        $userRole = $user->role();
        return $this->viewAny($user) &&
            ($this->checkHierarchy($userRole, $role) || $userRole->hierarchy === 0);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isAbleTo(DsPermission::ROLES_CREATE);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Role $role): bool
    {
        $userRole = $user->role();
        return $user->isAbleTo(DsPermission::ROLES_UPDATE) && $this->checkHierarchy($userRole, $role);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Role $role): bool
    {
        $userRole = $user->role();
        return $user->isAbleTo(DsPermission::ROLES_DELETE) && $this->checkHierarchy($userRole, $role);
    }

    private function checkHierarchy(Role $userRole, Role $role)
    {
        return $userRole->hierarchy < $role->hierarchy;
    }
}
