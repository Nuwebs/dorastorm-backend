<?php

namespace App\Utils\Auth;

use App\Models\Token;
use App\Models\User;
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
    public function login(JWTSubject $user): string
    {
        $request = $this->checkRequest($this->request);
        $payload = $this->jwt->makePayload($user);

        if (!($user instanceof Authenticatable)) {
            throw new JWTException('The user is not an instance of Authenticatable');
        }

        $token = $this->storeToken($payload, $request);

        $this->setToken($token)->setUser($user);

        return $token->get();
    }

    /**
     * Refresh the token.
     *
     * @param  bool  $forceForever
     * @param  bool  $resetClaims
     * @return string
     */
    public function refresh($forceForever = false, $resetClaims = false): string
    {
        $request = $this->checkRequest($this->request);
        $newStrToken = parent::refresh($forceForever, $resetClaims);

        $newToken = new JWT($newStrToken);
        $payload = $this->jwt->manager()->decode($newToken);

        $this->storeToken($payload, $request);

        return $newStrToken;
    }

    /**
     * Invalidate the token.
     *
     * @param  bool  $forceForever
     * @param string|null $token
     * @return \Tymon\JWTAuth\JWT
     */
    public function invalidate($forceForever = false, string $token = null)
    {
        if (is_null($token)) {
            return parent::invalidate($forceForever);
        }

        $jwt = clone $this->jwt;
        $jwt->setToken($token);
        $jwt->invalidate($forceForever);

        return $jwt;
    }

    /**
     * Create a new token by User id.
     *
     * @param  mixed  $id
     * @param int|null $customTTL
     * @param bool $isApiKey
     * @return string|null
     */
    public function tokenById($id, int|null $customTTL = null, bool $isApiKey = false)
    {
        $request = $this->checkRequest($this->request);

        $user = $id instanceof User ? $id : $this->provider->retrieveById($id);

        if (!$user || !($user instanceof User)) {
            if (!$user) {
                return null;
            }
            throw new \Exception('The provided authenticatable is not a valid user.');
        }

        if (!is_null($customTTL)) {
            $this->factory()->setTTL($customTTL);
        }

        $payload = $this->jwt->makePayload($user);

        return $this->storeToken($payload, $request, $isApiKey)->get();
    }

    protected function storeToken(Payload $payload, Request $request, bool $isApiKey = false): JWT
    {
        $encoded = $this->jwt->manager()->encode($payload);

        Token::create([
            'user_id' => $payload->get('sub'),
            'key' => $payload->get('jti'),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'expire_date' => Carbon::parse($payload->get('exp')),
            'encoded' => $encoded,
            'is_api_key' => $isApiKey
        ]);

        return $encoded;
    }

    protected function checkRequest(Request|null $request): Request
    {
        if (is_null($request)) {
            throw new JWTException('A request is required as a parameter in order to login');
        }
        return $request;
    }
}
