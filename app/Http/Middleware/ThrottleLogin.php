<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ThrottleLogin
{
    public function handle(Request $request, Closure $next, $maxAttempts = 5, $decayMinutes = 1): Response
    {
        $key = 'login.attempts.' . sha1($request->ip());

        $limiter = app(\Illuminate\Cache\RateLimiter::class);

        if ($limiter->tooManyAttempts($key, $maxAttempts)) {
            $seconds = $limiter->availableIn($key);
            return response()->json([
                'message' => 'Too many login attempts. Try again in ' . $seconds . ' seconds.',
            ], 429);
        }

        $response = $next($request);

        $limiter->hit($key, $decayMinutes * 60);

        return $response;
    }
}