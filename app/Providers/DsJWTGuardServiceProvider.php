<?php

namespace App\Providers;

use App\Utils\Auth\DsJWTGuard;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Providers\LaravelServiceProvider;

class DsJWTGuardServiceProvider extends LaravelServiceProvider
{
    /**
     * Extend Laravel's Auth.
     *
     * @return void
     */
    protected function extendAuthGuard()
    {
        Auth::extend('dsjwt', function ($app, $name, array $config) {
            $guard = new DsJWTGuard(
                $app['tymon.jwt'],
                $app['auth']->createUserProvider($config['provider']),
                $app['request']
            );

            $app->refresh('request', $guard, 'setRequest');

            return $guard;
        });
    }
}
