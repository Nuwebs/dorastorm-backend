<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use App\Rules\UserRoleRule;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if (!$request->user()->can('viewAny', User::class))
            abort(403);
        return UserResource::collection(User::paginate(15));
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
        if (!$request->user()->can('create', User::class))
            abort(403);
        $data = $request->validate([
            'name' => 'required|string|max:191',
            'email' => 'required|unique:users|email|max:191',
            'password' => 'required|string|max:191|min:6|confirmed',
            'password_confirmation' => 'required|string|max:191|min:6',
            'role_id' => [
                'required',
                'bail',
                'numeric',
                'min:1',
                'exists:roles,id',
                new UserRoleRule($request->user()->role())
            ]
        ]);
        $newUser = User::make($data);
        $newUser->password = Hash::make($data['password']);
        $newUser->save();
        $newUser->syncRoles([intval($data['role_id'])]);
        event(new Registered($newUser));
        return new UserResource($newUser);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        $user = User::findOrFail($id);
        if (!$request->user()->can('view', $user))
            abort(403);
        return new UserResource($user);
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

    public function showMe(Request $request)
    {
        // Temp
        return new UserResource(auth()->user());
    }
}