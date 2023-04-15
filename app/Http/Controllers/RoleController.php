<?php

namespace App\Http\Controllers;

use App\Http\Resources\RoleResource;
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
        return RoleResource::collection(Role::orderBy('hierarchy', 'asc')->get());
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
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

        $role->assignHierarchyAndSave($data['hierarchy'], true);
        $role->syncPermissions(
            $this->getAllowedPermissions($user->getAllPermissionsNames(), $data['permissions'])
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    private function appendPermissionsToValidation(array $validationRules, array $permissions): array
    {
        $validationRules['permissions'] = [
            'required',
            'array',
            'min:1',
            'in:'. implode(',', $permissions)
        ];
        return $validationRules;
    }

    private function getAllowedPermissions(array $userPermissions, array $requestedPermissions)
    {
        return array_intersect($requestedPermissions, $userPermissions);
    }
}