<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ThrottleApi
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $max = 60, $decay = 1)
    {
        $key = 'api.' . ($request->user()?->id ?: $request->ip());
        $limiter = app(\Illuminate\Cache\RateLimiter::class);

        if ($limiter->tooManyAttempts($key, $max)) {
            return response()->json(['message' => 'Rate limit exceeded.'], 429);
        }

        $response = $next($request);
        $limiter->hit($key, $decay * 60);
        return $response;
    }
}
