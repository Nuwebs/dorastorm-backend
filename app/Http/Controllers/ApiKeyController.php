<?php

namespace App\Http\Controllers;

use App\Models\Token;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Token::class);
        $data = $request->validate([
            'user_id' => 'required|integer|exists:users,id'
        ]);

        $user = User::findOrFail((int) $data['user_id']);

        $token = auth()->tokenById($user, 52560000, true);

        if (!is_string($token)) {
            abort(Response::HTTP_INTERNAL_SERVER_ERROR, 'The generated token is invalid');
        }

        return response()->json([
            'key' => $token
        ], Response::HTTP_OK);
    }

    public function revoke(Request $request, string $apiKey): JsonResponse
    {
        $token = Token::where('encoded', $apiKey)->where('revoked', false)->firstOrFail();
        $this->authorize('delete', $token);

        auth()->invalidate(false, $apiKey);

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
