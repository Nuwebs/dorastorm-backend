<?php

use App\Http\Middleware\ForceJson;
use App\Http\Middleware\SetApiLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',

        api: __DIR__ . '/../routes/api.php',
        apiPrefix: '',

        commands: __DIR__ . '/../routes/console.php',

        // health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->preventRequestsDuringMaintenance([
            '/mtc/*'
        ]);
        $middleware->append(ForceJson::class);

        $middleware->api(prepend: [
            SetApiLocale::class
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
