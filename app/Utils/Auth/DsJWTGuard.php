<?php

namespace App\Utils\Auth;

use App\Models\Token;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\JWTGuard;
use Tymon\JWTAuth\Payload;
use Tymon\JWTAuth\Token as JWT;

class DsJWTGuard extends JWTGuard
{
    public function login(JWTSubject $user, Request $request = null): string
    {
        $request = $this->checkRequest($request);
        $payload = $this->jwt->makePayload($user);

        if (!($user instanceof Authenticatable)) {
            throw new JWTException('The user is not an instance of Authenticatable');
        }

        $this->storeToken($payload, $request);

        $token = $this->jwt->manager()->encode($payload);
        $this->setToken($token)->setUser($user);

        return $token->get();
    }

    /**
     * Refresh the token.
     *
     * @param  bool  $forceForever
     * @param  bool  $resetClaims
     * @param Request $request
     * @return string
     */
    public function refresh($forceForever = false, $resetClaims = false, Request $request = null): string
    {
        $request = $this->checkRequest($request);
        $newStrToken = parent::refresh($forceForever, $resetClaims);

        $newToken = new JWT($newStrToken);
        $payload = $this->jwt->manager()->decode($newToken);

        $this->storeToken($payload, $request);

        return $newStrToken;
    }

    protected function storeToken(Payload $payload, Request $request): void
    {
        Token::create([
            'user_id' => $payload->get('sub'),
            'key' => $payload->get('jti'),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'expire_date' => Carbon::parse($payload->get('exp'))
        ]);
    }

    protected function checkRequest(Request|null $request): Request
    {
        if (is_null($request)) {
            throw new JWTException('A request is required as a parameter in order to login');
        }
        return $request;
    }
}
