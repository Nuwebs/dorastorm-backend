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

    public function assignHierarchyAndSave(int $hierarchy, bool $creating = true): void
    {
        if ($creating || $hierarchy < $this->hierarchy) {
            $prev = $creating?  16777213 - 1: $this->hierarchy;
            $this->hierarchy = 16777213;
            $this->save();
            $rolesToChange = Role::where('hierarchy', '>=', $hierarchy)
                ->where('hierarchy', '<', $prev)
                ->orderBy('hierarchy', 'desc')->get();
            foreach ($rolesToChange as $role) {
                $role->hierarchy += 1;
                $role->save();
            }
            $this->hierarchy = $hierarchy;
            $this->save();
        } else {
            $prev = $this->hierarchy;
            $this->hierarchy = 16777213;
            $this->save();
            $rolesToChange = Role::where('hierarchy', '>', $prev)
                ->where('hierarchy', '<=', $hierarchy)
                ->orderBy('hierarchy')->get();
            foreach ($rolesToChange as $role) {
                $role->hierarchy -= 1;
                $role->save();
            }
            $this->hierarchy = $hierarchy;
            $this->save();
        }
    }
}
