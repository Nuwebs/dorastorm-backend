<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Laratrust\Models\Role as RoleModel;

class Role extends RoleModel
{
    const MAX_ROLE_HIERARCHY = 16777213;
    public $guarded = [];

    /**
     * @return Collection<int, Permission>
     */
    public static function getAllDsPermissions(): Collection
    {
        $superAdmin = Role::where('hierarchy', 0)->firstOrFail();
        // @phpstan-ignore-next-line
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

    public function assignHierarchy(int $hierarchy, bool $creating = true): void
    {
        // Prevent the execution of this function if is not necessary.
        if ($hierarchy === $this->hierarchy)
            return;

        // Prevent hierarchies with numbers greater than the assigned to the lowest priority role
        $maxHierarchyAvailable = Role::max('hierarchy') + 1;
        if ($hierarchy > $maxHierarchyAvailable)
            $hierarchy = $maxHierarchyAvailable;

        // Prevent assign a hierarchy with better priority than the superadmin (hierarchy 0)
        if ($hierarchy < 1)
            $hierarchy = 1;

        $prev = $creating ? Role::MAX_ROLE_HIERARCHY - 1 : $this->hierarchy;
        $this->hierarchy = Role::MAX_ROLE_HIERARCHY;
        $this->save();
        $this->makeHierarchyRoom($hierarchy, $prev, $creating);
        $this->hierarchy = $hierarchy;
        $this->save();
    }

    private function makeHierarchyRoom(int $hierarchy, int $prevHierarchy, bool $creating): void
    {
        $increment = 1;
        if ($creating || $hierarchy < $prevHierarchy) {
            $rolesToChange = Role::where('hierarchy', '>=', $hierarchy)
                ->where('hierarchy', '<', $prevHierarchy)
                ->orderBy('hierarchy', 'desc')->get();
        } else {
            $rolesToChange = Role::where('hierarchy', '>', $prevHierarchy)
                ->where('hierarchy', '<=', $hierarchy)
                ->orderBy('hierarchy')->get();
            $increment = -1;
        }
        foreach ($rolesToChange as $role) {
            $role->hierarchy += $increment;
            $role->save();
        }
    }
}
