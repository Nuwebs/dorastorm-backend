<?php

namespace App\Http\Controllers;

use App\Http\Resources\RoleResource;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\Role;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpFoundation\Response;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Role::class);

        $results = QueryBuilder::for(Role::orderBy('hierarchy', 'asc'))->allowedFilters([
            AllowedFilter::callback('global', function (Builder $query, $value) {
                $query->where('name', 'LIKE', "%$value%")
                    ->orWhere('display_name', 'LIKE', "%$value%")
                    ->orWhere('description', 'LIKE', "%$value%")
                    ->orWhere('hierarchy', '=', $value)
                    // phpcs:ignore reason: Not stated in docs
                    ->orWhereHas('permissions', function ($query) use ($value) {
                        $query->where('name', 'LIKE', "%$value%");
                    });
            })
        ])->paginate(25);
        return RoleResource::collection($results);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RoleResource
    {
        $this->authorize('create', Role::class);

        $user = $request->user() ?? abort(Response::HTTP_FORBIDDEN);
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

        return new RoleResource($role);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id): RoleResource
    {
        $role = Role::findOrFail($id);
        $this->authorize('view', $role);

        return new RoleResource($role);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $user = $request->user() ?? abort(Response::HTTP_FORBIDDEN);
        $role = Role::findOrFail($id);
        $this->authorize('update', $role);

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

        return response()->json(null, Response::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $role = Role::findOrFail($id);
        $this->authorize('delete', $role);

        $nUsersUsingRole = User::whereHasRole($role->name)->count();

        if ($nUsersUsingRole > 0)
            abort(422, 'There are users using this role');

        $role->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @param array<string, string> $validationRules
     * @param array<string> $permissions
     * @return array<string, array<string>|string>
     */
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

    /**
     * @param array<string> $userPermissions
     * @param array<string> $requestedPermissions
     * @return array<string>
     */
    private function getAllowedPermissions(array $userPermissions, array $requestedPermissions): array
    {
        return array_intersect($requestedPermissions, $userPermissions);
    }
}
