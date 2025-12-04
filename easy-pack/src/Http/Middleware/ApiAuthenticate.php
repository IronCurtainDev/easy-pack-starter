<?php

namespace EasyPack\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiAuthenticate
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if API is active
        if (function_exists('has_feature') && !has_feature('api.active')) {
            return $next($request);
        }

        // Allow documentation generation to bypass API key check
        if ($request->header('X-Documentation-Mode') === 'true') {
            return $next($request);
        }

        // Get the API token from the header
        $apiToken = $request->header('x-api-key');
        
        // Get the valid API token(s) from config
        $validApiToken = config('easypack.api_key');
        
        // Support multiple API keys separated by commas
        $validApiTokens = $validApiToken ? explode(',', $validApiToken) : [];
        
        // Trim whitespace from each token
        $validApiTokens = array_map('trim', $validApiTokens);
        
        // Check if API token is missing
        if (empty($apiToken)) {
            return response()->json([
                'result' => false,
                'message' => 'An API Key is required',
                'type' => 'INVALID_PARAMETER_API_KEY'
            ], 401);
        }
        
        // Check if the provided token is valid
        if (!in_array($apiToken, $validApiTokens, true)) {
            return response()->json([
                'result' => false,
                'message' => 'A valid API Key is required',
                'type' => 'INVALID_PARAMETER_API_KEY'
            ], 401);
        }
        
        return $next($request);
    }
}
