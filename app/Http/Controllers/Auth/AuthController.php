<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use App\Http\Requests\LoginRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class AuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        $token = $request->authenticate();
        return $this->respondWithToken($token);
    }

    public function refreshToken()
    {
        try {
            return $this->respondWithToken(auth()->refresh());
        } catch (TokenExpiredException) {
            return response()->json(['message' => __('auth.expired_token')], 409);
        } catch (TokenInvalidException) {
            return response()->json(['message' => __('auth.invalid_token')], 422);
        } catch (JWTException $e) {
            abort(422, $e);
        }
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
            ], 422);
        }

        return response()->json(['status' => __($status)], 200);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed'
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));

                $user->save();
                event (new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return response()->json([
                'errors' => ['email' => __($status)]
            ], 422);
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
