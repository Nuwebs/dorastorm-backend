<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetApiLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->expectsJson() || !$request->hasHeader('Accept-Language'))
            return $next($request);

        $locale = $request->header('Accept-Language');
        app()->setLocale($locale ?? config('app.fallback_locale'));

        return $next($request);
    }
}
