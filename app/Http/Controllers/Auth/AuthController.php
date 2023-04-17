<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use App\Http\Requests\LoginRequest;
use Illuminate\Support\Facades\Password;

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

    public function verifyEmail(EmailVerificationRequest $request)
    {
        $request->fulfill();
    }

    public function sendResetPasswordLink(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $status = Password::sendResetLink($request->only('email'));

        if ($status !== Password::RESET_LINK_SENT) {
            return response()->json([
                'errors' => ['email' => __($status)]
            ]);
        }

        return response()->json(['status' => __($status)], 200);
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