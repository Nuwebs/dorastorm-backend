<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\LoginRequest;

class AuthController extends Controller
{
    public function login(LoginRequest $request) 
    {
        $token = $request->authenticate();
        return $this->respondWithToken($token);
    }

    public function logout(Request $request)
    {
        auth()->logout();
    }

    protected function respondWithToken($token)
    {
        return response()->json([
            'accessToken' => $token,
            'tokenType' => 'bearer',
            'expiresIn' => auth('api')->factory()->getTTL() * 60
        ]);
    }
}
