<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-Api-Key');
        $validApiKey = config('app.admin_api_key');

        if (!$apiKey || !$validApiKey || !hash_equals($validApiKey, $apiKey)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid or missing API key',
                'correlation_id' => $request->header('X-Correlation-ID', uniqid('req_'))
            ], 401);
        }

        return $next($request);
    }
}
