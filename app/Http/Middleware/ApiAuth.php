<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class ApiAuth
{
    private $expectedAuthKey;

    public function __construct()
    {
        $this->expectedAuthKey = env('AUTH_KEY');

        if (! $this->expectedAuthKey) {
            \Log::error('AUTH_KEY not found.');
        }
    }

    public function handle(Request $request, Closure $next): Response
    {
        // Read the 'AUTH' value from the custom header
        $auth = $request->header('AUTH');

        if (! $auth || $auth !== $this->expectedAuthKey) {
            \Log::warning('Unauthorized access attempt', ['auth' => $auth]);

            return response('Unauthorized', 401);
        }

        // Rate limiting
        $key = sprintf('api:%s', $request->ip());
        if (RateLimiter::tooManyAttempts($key, 60)) {
            return response('Too many requests', 429);
        }

        RateLimiter::hit($key, 60);

        return $next($request);
    }
}
