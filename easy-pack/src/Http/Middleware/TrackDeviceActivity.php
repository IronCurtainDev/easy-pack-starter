<?php

namespace EasyPack\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to track device activity.
 * Updates the last_used_at timestamp and IP address for the current access token.
 */
class TrackDeviceActivity
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
        // Process the request first
        $response = $next($request);

        // If authenticated, update the token's activity tracking
        if ($request->user()) {
            $token = $request->user()->currentAccessToken();

            if ($token) {
                // Update the latest IP address (Sanctum already handles last_used_at)
                $ipAddress = $request->ip();

                if ($ipAddress && $token->latest_ip_address !== $ipAddress) {
                    $token->updateQuietly(['latest_ip_address' => $ipAddress]);
                }
            }
        }

        return $response;
    }
}
