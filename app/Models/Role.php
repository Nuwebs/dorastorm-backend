<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Laratrust\Models\Role as RoleModel;

class Role extends RoleModel
{
    public $guarded = [];

    public static function getAllDsPermissions(): Collection
    {
        $superAdmin = Role::where('hierarchy', 0)->firstOrFail();
        return $superAdmin->permissions;
    }

    public static function getAllDsPermissionsNames(): array
    {
        $allPermissions = Role::getAllDsPermissions();
        $permissionsNames = [];
        foreach ($allPermissions as $permission) {
            array_push($permissionsNames, $permission->name);
        }
        return $permissionsNames;
    }

    /**
    * Deletes the current Role model instance from the database and updates the hierarchy values of other roles accordingly. Returns a boolean indicating whether the deletion was successful.
    * @return bool A boolean indicating whether the deletion was successful.
    */
    public function delete(): bool
    {
        if (!parent::delete())
            return false;

        $rolesBelow = Role::where('hierarchy', '>', $this->hierarchy)->orderBy('hierarchy', 'asc')->get();
        foreach ($rolesBelow as $role) {
            $role->hierarchy -= 1;
            $role->save();
        }
        return true;
    }

    /**
     * Assigns a hierarchy value to a Role model instance and saves it to the database.
     * If the $creating parameter is set to true or if the new hierarchy value is greater
     * than the current hierarchy value, it will call the makeHierarchyRoom() method
     * to make room for the new hierarchy value. Otherwise, it sets the hierarchy value
     * to the max possible hierarchy value minus 2 and calls makeHierarchyRoom() with the $updateAbove
     * and $prev parameters to move the other roles that have hierarchy values between
     * the old and new hierarchy values. If no roles were moved in the process, it sets
     * the hierarchy value to the maximum hierarchy value + 1.
     * @param int $hierarchy The new hierarchy value to assign to the Role model instance.
     * @param bool $creating A boolean indicating whether the Role is being created (true)
     * or updated (false). Defaults to true.
     * @return void
     */
    public function assignHierarchyAndSave(int $hierarchy, bool $creating = true): void
    {
        $moved = false;
        if ($creating || $hierarchy > $this->hierarchy) {
            $moved = $this->makeHierarchyRoom($hierarchy);
        } else {
            $prev = $this->hierarchy;
            $this->hierarchy = 16777213;
            $this->save();
            $moved = $this->makeHierarchyRoom($hierarchy, true, $prev);
        }
        if (!$moved) {
            $hierarchy = Role::max('hierarchy') + 1;
        }
        $this->hierarchy = $hierarchy;
        $this->save();
    }

    /**
     * Moves other roles that have hierarchy values greater than or equal to the
     * specified hierarchy value down by 1 to make room for a new role with the
     * specified hierarchy value. If the $updateAbove parameter is set to true,
     * it also moves other roles that have hierarchy values between the old and
     * new hierarchy values down by 1.
     * @param int $hierarchy The hierarchy value to make room for.
     * @param bool $updateAbove A boolean indicating whether to update the roles above
     * the old hierarchy value (true) or only the roles above the new hierarchy value (false).
     * Defaults to false.
     * @param int $prev The previous hierarchy value. Only used if $updateAbove is true. Defaults to -1.
     * @return bool A boolean indicating whether any roles were moved.
     */
    private function makeHierarchyRoom(int $hierarchy, bool $updateAbove = false, int $prev = -1): bool
    {
        $rolesToChange = Role::where('hierarchy', '>=', $hierarchy);
        if ($updateAbove)
            $rolesToChange = $rolesToChange->where('hierarchy', '<', $prev);
        $rolesToChange = $rolesToChange->orderBy('hierarchy', 'desc')->get();
        foreach ($rolesToChange as $role) {
            $role->hierarchy += 1;
            $role->save();
        }
        return $rolesToChange->isNotEmpty();
    }
}