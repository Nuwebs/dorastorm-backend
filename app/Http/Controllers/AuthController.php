<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Requests\LoginRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {

        $request->authenticate();
        $user = auth()->getLastAttempted();

        if (!$user instanceof JWTSubject) {
            abort(Response::HTTP_INTERNAL_SERVER_ERROR, 'The user is not a JWT Subject');
        }

        // phpcs:ignore reason: The auth guard is the dsjwt. It has a login method that returns a string
        $token = auth()->login($user, $request);

        return $this->respondWithToken($token);
    }

    public function refreshToken(Request $request): JsonResponse
    {
        try {
            // phpcs:ignore reason: The auth guard is the dsjwt. It has a request parameter
            $token = auth()->refresh(false, false, $request);
            return $this->respondWithToken($token);
        } catch (TokenExpiredException) {
            return response()->json(['message' => __('auth.expired_token')], Response::HTTP_CONFLICT);
        } catch (TokenInvalidException) {
            return response()->json(['message' => __('auth.invalid_token')], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (JWTException $e) {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY, $e);
        }
    }

    public function logout(Request $request): void
    {
        auth()->logout();
    }

    public function verifyEmail(Request $request): void
    {
        $id = $request->route('id');
        $hash = $request->route('hash');

        if (!is_string($id) || !is_string($hash)) {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = User::find($id);
        if (!(!empty($user) && hash_equals(sha1($user->getEmailForVerification()), $hash)))
            abort(Response::HTTP_UNPROCESSABLE_ENTITY);

        if (!$user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
        }
    }

    public function resendEmailVerification(Request $request): void
    {
        $request->user()?->sendEmailVerificationNotification();
    }

    public function sendResetPasswordLink(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $status = Password::sendResetLink($request->only('email'));

        if ($status !== Password::RESET_LINK_SENT) {
            return response()->json([
                'errors' => ['email' => __($status)]
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json(['status' => __($status)], Response::HTTP_OK);
    }

    public function resetPassword(Request $request): JsonResponse
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
                ]);

                $user->save();
                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return response()->json([
                'errors' => ['email' => __($status)]
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json(['status' => __($status)], Response::HTTP_OK);
    }

    protected function respondWithToken(string $token): JsonResponse
    {
        return response()->json([
            'accessToken' => $token,
            'tokenType' => 'bearer',
            'expiresIn' => auth()->factory()->getTTL() * 60
        ]);
    }
}
