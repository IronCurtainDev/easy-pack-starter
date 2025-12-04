<?php

namespace EasyPack\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to convert X-Access-Token header to Authorization Bearer token.
 * This allows mobile apps and other clients to send the token in a custom header
 * while still using Laravel Sanctum's standard authentication.
 */
class ConvertXAccessToken
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if X-Access-Token header is present and Authorization is not
        if ($request->hasHeader('X-Access-Token') && !$request->hasHeader('Authorization')) {
            $token = $request->header('X-Access-Token');

            // Set the Authorization header with Bearer prefix
            $request->headers->set('Authorization', 'Bearer ' . $token);
        }

        return $next($request);
    }
}
