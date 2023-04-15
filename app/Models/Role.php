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