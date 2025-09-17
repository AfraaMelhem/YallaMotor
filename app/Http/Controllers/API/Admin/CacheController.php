<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Services\CacheService;
use App\Traits\BaseResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CacheController extends Controller
{
    use BaseResponse;

    public function __construct(
        protected CacheService $cacheService
    ) {}

    public function purge(Request $request): JsonResponse
    {
        $startTime = microtime(true);
        $correlationId = $request->header('X-Correlation-ID', uniqid('cache_purge_'));

        // Validate the request
        $validator = Validator::make($request->all(), [
            'keys' => 'sometimes|array',
            'keys.*' => 'string|max:255',
            'tags' => 'sometimes|array',
            'tags.*' => 'string|max:255|regex:/^[a-zA-Z0-9_:{}.-]+$/',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid request data',
                'errors' => $validator->errors(),
                'correlation_id' => $correlationId
            ], 400);
        }

        try {
            $keys = $request->input('keys', []);
            $tags = $request->input('tags', []);
            $purgedKeys = [];

            Log::info('Cache purge requested', [
                'correlation_id' => $correlationId,
                'keys_count' => count($keys),
                'tags_count' => count($tags),
                'keys' => $keys,
                'tags' => $tags,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            // Purge by specific keys
            if (!empty($keys)) {
                $keysPurged = $this->cacheService->flushByKeys($keys);
                $purgedKeys = array_merge($purgedKeys, $keysPurged);
            }

            // Purge by tags
            if (!empty($tags)) {
                $tagsPurged = $this->cacheService->flush($tags);
                $purgedKeys = array_merge($purgedKeys, $tagsPurged);
            }

            // If neither keys nor tags provided, clear all cache
            if (empty($keys) && empty($tags)) {
                $result = $this->cacheService->flush();
                $purgedKeys = ['all_cache_cleared' => true];
            }

            $queryTime = round((microtime(true) - $startTime) * 1000, 2);

            Log::info('Cache purge completed', [
                'correlation_id' => $correlationId,
                'purged_keys_count' => count($purgedKeys),
                'query_time_ms' => $queryTime
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Cache purged successfully',
                'data' => [
                    'purged_keys' => $purgedKeys,
                    'purged_count' => count($purgedKeys),
                    'query_time_ms' => $queryTime
                ],
                'correlation_id' => $correlationId
            ]);

        } catch (\Exception $e) {
            $queryTime = round((microtime(true) - $startTime) * 1000, 2);

            Log::error('Cache purge failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'query_time_ms' => $queryTime
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Cache purge failed',
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId
            ], 500);
        }
    }

    public function status(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', uniqid('cache_status_'));

        try {
            // Get basic cache statistics
            $stats = [
                'cache_driver' => config('cache.default'),
                'timestamp' => now()->toISOString(),
                'uptime_check' => 'ok'
            ];

            return response()->json([
                'status' => 'success',
                'message' => 'Cache status retrieved',
                'data' => $stats,
                'correlation_id' => $correlationId
            ]);

        } catch (\Exception $e) {
            Log::error('Cache status check failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Cache status check failed',
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId
            ], 500);
        }
    }
}
