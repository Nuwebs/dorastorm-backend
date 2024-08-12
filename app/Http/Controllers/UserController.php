<?php

namespace App\Http\Controllers;

use App\Http\Resources\JWTResource;
use App\Http\Resources\RoleResource;
use App\Http\Resources\UserResource;
use App\Models\Role;
use App\Models\Token;
use App\Models\User;
use App\Rules\UserRoleRule;
use Illuminate\Auth\Events\Registered;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Hash;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{
    /**
     * @var array<string, string>
     */
    protected array $newUserValidations = [
        'name' => 'required|string|max:191',
        'email' => 'required|unique:users|email|max:191',
        'password' => 'required|string|max:191|min:8|confirmed',
        'password_confirmation' => 'required|string|max:191|min:8',
    ];

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', User::class);

        $results = QueryBuilder::for(User::class)->allowedFilters([
            AllowedFilter::callback('global', function (Builder $query, $value) {
                $query->where('name', 'LIKE', "%$value%")
                    ->orWhere('email', 'LIKE', "%$value%");
            })
        ])->paginate(25);
        return UserResource::collection($results);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id): UserResource
    {
        $user = User::findOrFail($id);
        $this->authorize('view', $user);

        return new UserResource($user);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): UserResource
    {
        $this->authorize('create', User::class);
        $user = $request->user() ?? abort(Response::HTTP_FORBIDDEN);

        $data = $request->validate(
            array_merge(
                $this->newUserValidations,
                $this->getRoleValidationRules($user->role())
            )
        );

        $newUser = $this->createNewUser($data);
        // If there isn't any role_id in the request, the role will be the lowest in the hierarchy.
        $roleId = !empty($data['role_id']) ?
            intval($data['role_id']) : Role::orderby('hierarchy', 'desc')->first()?->id;
        $newUser->syncRoles([$roleId]);

        return new UserResource($newUser);
    }

    public function signUp(Request $request): UserResource
    {
        $data = $request->validate($this->newUserValidations);

        $newUser = $this->createNewUser($data);
        $newUser->addRole(config('laratrust.most_basic_role_name'));

        return new UserResource($newUser);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $this->authorize('update', $user);
        $currentUser = $request->user() ?? abort(Response::HTTP_FORBIDDEN);

        $validations = array_merge(
            [
                'name' => 'required|string|max:191',
                'email' => 'required|email|max:191|unique:users,email,' . $user->id,
            ],
            $this->getRoleValidationRules($currentUser->role())
        );
        $data = $request->validate($validations);

        $toUpdateUserRole = $user->role();
        $roleChanged = !empty($data['role_id']) && $toUpdateUserRole->id != $data['role_id'];

        // Check if the user is the last superadmin left and he is trying to change his role
        if (
            $toUpdateUserRole->hierarchy === 0 &&
            $roleChanged && $this->isLastAdminLeft($toUpdateUserRole)
        ) {
            abort(409, trans('validation.custom.user_destroy.sole_admin'));
        }

        $user->name = $data['name'];
        $user->email = $data['email'];

        $user->save();
        if ($roleChanged)
            $user->syncRoles([intval($data['role_id'])]);

        return response()->json(null, Response::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $this->authorize('delete', $user);

        if ($this->isLastAdminLeft($user->role()))
            abort(409, trans('validation.custom.user_destroy.sole_admin'));

        $user->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    public function showMe(Request $request): UserResource
    {
        return new UserResource(auth()->user());
    }

    public function updatePassword(Request $request, string $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $this->authorize('update', $user);

        $data = $request->validate([
            'password' => 'required|string|max:191|min:6|confirmed',
            'password_confirmation' => 'required|string|min:6',
            'current_password' => [
                'required',
                'string',
                'max:191',
                'min:6',
                function ($attribute, $value, $fail) use ($user) {
                    if (!Hash::check($value, $user->password)) {
                        $fail(trans('auth.password'));
                    }
                }
            ],
        ]);
        $user->password = Hash::make($data['password']);
        $user->save();

        return response()->json(null, Response::HTTP_OK);
    }

    public function rolesBelow(Request $request): AnonymousResourceCollection
    {
        $user = $request->user() ?? abort(Response::HTTP_FORBIDDEN);
        $userRoleHierarchy = $user->role()->hierarchy;
        $roles = Role::where('hierarchy', '>', ($userRoleHierarchy === 0) ? -1 : $userRoleHierarchy)
            ->orderBy('hierarchy', 'asc')->get();
        return RoleResource::collection($roles);
    }

    public function activeSessions(Request $request): AnonymousResourceCollection
    {
        $user = $request->user() ?? abort(Response::HTTP_FORBIDDEN);

        $activeSessions = $user->tokens()->where('revoked', false)->get();

        return JWTResource::collection($activeSessions);
    }

    public function revokeSession(Token $token): JsonResponse
    {
        // phpcs:ignore reason: The auth guard is the dsjwt. The invalidate method accepts the encoded token to be invalidated
        auth()->invalidate(false, $token->encoded);

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Summary of createNewUser
     * @param array<string, mixed> $data
     * @return \App\Models\User
     */
    private function createNewUser(array $data): User
    {
        $newUser = new User($data);
        $newUser->password = Hash::make($data['password']);
        $newUser->save();
        event(new Registered($newUser));

        return $newUser;
    }

    /**
     * @return array<string, array<int, string|UserRoleRule>>
     */
    private function getRoleValidationRules(Role $currentUserRole): array
    {
        return [
            'role_id' => [
                'nullable',
                'bail',
                'numeric',
                'min:1',
                'exists:roles,id',
                new UserRoleRule($currentUserRole)
            ]
        ];
    }

    private function isLastAdminLeft(Role $toUpdateUserRole): bool
    {
        $superAdminRoleName = Role::where('hierarchy', '=', 0)->firstOrFail()->name;
        if ($toUpdateUserRole->hierarchy === 0 && User::whereHasRole($superAdminRoleName)->count() < 2) {
            return true;
        }
        return false;
    }
}
