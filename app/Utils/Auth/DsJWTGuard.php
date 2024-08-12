<?php

namespace App\Utils\Auth;

use Illuminate\Contracts\Auth\Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\JWTGuard;

class DsJWTGuard extends JWTGuard
{
    public function login(JWTSubject $user): string
    {
        $payload = $this->jwt->makePayload($user);

        if (!($user instanceof Authenticatable)) {
            throw new JWTException('The user is not an instance of Authenticatable');
        }
        // Guardar token en BD


        $token = $this->jwt->manager()->encode($payload);
        $this->setToken($token)->setUser($user);

        return $token->get();
    }
}
