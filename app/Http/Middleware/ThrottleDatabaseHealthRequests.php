<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ThrottleDatabaseHealthRequests
{
    private RateLimiter $limiter;

    public function __construct(CacheFactory $cache)
    {
        $this->limiter = new RateLimiter(
            $cache->store(config('cache.health_limiter_store', 'file'))
        );
    }

    public function handle(Request $request, Closure $next): Response
    {
        $key = 'database-health:'.sha1((string) $request->ip());

        if ($this->limiter->tooManyAttempts($key, 12)) {
            return response()
                ->json(['message' => 'Too Many Requests.'], 429)
                ->header('Cache-Control', 'no-store, private')
                ->header('Retry-After', (string) $this->limiter->availableIn($key));
        }

        $this->limiter->hit($key, 60);

        return $next($request);
    }
}
