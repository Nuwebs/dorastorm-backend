<?php

namespace App\Http\Controllers;

use App\Http\Resources\RoleResource;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Role;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if (!$request->user()->can('viewAny', Role::class))
            abort(403);
        return RoleResource::collection(Role::orderBy('hierarchy', 'asc')->paginate(15));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = $request->user();

        if (!$user->can('create', Role::class))
            abort(403);
        $allPermissions = Role::getAllDsPermissionsNames();

        $validationRules = [
            'name' => 'required|unique:roles|min:2|max:50',
            'hierarchy' => 'required|numeric|min:1|gt:' . $user->role()->hierarchy,
            'description' => 'string|nullable',
        ];
        $data = $request->validate($this->appendPermissionsToValidation($validationRules, $allPermissions));

        $role = new Role();
        $role->name = strtolower($data['name']);
        $role->display_name = ucwords($data['name']);
        $role->description = $data['description'] ?? null;

        $role->assignHierarchy($data['hierarchy'], true);
        $role->syncPermissions(
            $this->getAllowedPermissions($user->getAllPermissionsNames(), $data['permissions'])
        );

        return response('', 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        $role = Role::findOrFail($id);
        if (!$request->user()->can('view', $role))
            abort(403);

        return new RoleResource($role);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = $request->user();
        $role = Role::findOrFail($id);

        if (!$user->can('update', $role))
            abort(403);

        $allPermissions = Role::getAllDsPermissionsNames();

        $validationRules = [
            'name' => 'required|unique:roles,name,' . $role->id . '|min:2|max:50',
            'hierarchy' => 'required|gt:' . $user->role()->hierarchy,
            'description' => 'string|nullable'
        ];
        $data = $request->validate($this->appendPermissionsToValidation($validationRules, $allPermissions));

        $role->name = strtolower($data['name']);
        $role->display_name = ucwords($data['name']);
        $role->description = $data['description'] ?? null;
        $role->save();

        $role->assignHierarchy($data['hierarchy'], false);
        $role->syncPermissions(
            $this->getAllowedPermissions($user->getAllPermissionsNames(), $data['permissions'])
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        $role = Role::findOrFail($id);
        if (!$request->user()->can('delete', $role))
            abort(403);

        $nUsersUsingRole = User::whereHasRole($role->name)->count();

        if ($nUsersUsingRole > 0)
            abort(422, 'There are users using this role');

        $role->delete();
    }

    private function appendPermissionsToValidation(array $validationRules, array $permissions): array
    {
        $validationRules['permissions'] = [
            'required',
            'array',
            'min:1',
            'in:' . implode(',', $permissions)
        ];
        return $validationRules;
    }

    private function getAllowedPermissions(array $userPermissions, array $requestedPermissions)
    {
        return array_intersect($requestedPermissions, $userPermissions);
    }
}
