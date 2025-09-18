<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\BaseResource;
use App\Services\CarService;
use App\Traits\BaseResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;

class CarController extends Controller
{
    use BaseResponse;

    public function __construct(
        protected CarService $carService
    ) {}

    public function getPaginatedList(Request $request): JsonResponse
    {
        $startTime = microtime(true);

        try {
            $data = $this->prepareListData($request);
            $perPage = min($request->get('per_page', 20), 50);
            $includeFacets = $request->boolean('include_facets', false);

            $result = $this->carService->getFilteredCars($data, $perPage, $includeFacets);

            $queryTime = round((microtime(true) - $startTime) * 1000, 2);

            // Generate ETag for cache validation
            $etag = md5(serialize($result['cars']->items()) . $result['cars']->total() . serialize($data));

            // Check If-None-Match header for 304 response
            if ($request->header('If-None-Match') === $etag) {
                return response()->json(null, 304)
                    ->header('Cache-Control', 'public, max-age=300')
                    ->header('ETag', $etag)
                    ->header('X-Cache', 'HIT-304')
                    ->header('X-Cache-Key', $result['cache_key'])
                    ->header('X-Query-Time-ms', $queryTime);
            }

            $responseData = [
                'data' => BaseResource::collection($result['cars'])->resolve(),
                'meta' => [
                    'pagination' => [
                        'current_page' => $result['cars']->currentPage(),
                        'per_page' => $result['cars']->perPage(),
                        'total' => $result['cars']->total(),
                        'last_page' => $result['cars']->lastPage(),
                        'from' => $result['cars']->firstItem(),
                        'to' => $result['cars']->lastItem(),
                    ],
                    'filters_applied' => $data['filters'],
                    'query_time_ms' => $queryTime,
                ]
            ];

            // Add facets if requested
            if ($includeFacets && $result['facets']) {
                $responseData['facets'] = $result['facets'];
            }

            $response = $this->successResponse(
                'Cars retrieved successfully',
                $responseData,
                200,
                $request
            );

            // Add CDN and observability headers
            return $response
                ->header('Cache-Control', 'public, max-age=300, s-maxage=300')
                ->header('ETag', $etag)
                ->header('Vary', 'Accept, Accept-Encoding')
                ->header('X-Cache', cache()->has($result['cache_key']) ? 'HIT' : 'MISS')
                ->header('X-Cache-Key', $result['cache_key'])
                ->header('X-Query-Time-ms', $queryTime)
                ->header('X-Total-Cars', $result['cars']->total())
                ->header('X-API-Version', 'v1');

        } catch (Exception $e) {
            return $this->errorResponse('Failed to retrieve cars', 500)
                ->header('X-Cache', 'MISS')
                ->header('X-Query-Time-ms', round((microtime(true) - $startTime) * 1000, 2));
        }
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $startTime = microtime(true);

        try {
            $car = $this->carService->showWithRelations($id);

            // Generate ETag for cache validation
            $etag = md5(serialize($car->toArray()));

            // Check If-None-Match header for 304 response
            if ($request->header('If-None-Match') === $etag) {
                return response()->json(null, 304)
                    ->header('Cache-Control', 'public, max-age=600')
                    ->header('ETag', $etag)
                    ->header('X-Cache', 'HIT-304')
                    ->header('X-Cache-Key', "car_full:{$id}")
                    ->header('X-Query-Time-ms', round((microtime(true) - $startTime) * 1000, 2));
            }

            $queryTime = round((microtime(true) - $startTime) * 1000, 2);

            $response = $this->successResponse(
                'Car retrieved successfully',
                new BaseResource($car)
            );

            // Add CDN and observability headers
            return $response
                ->header('Cache-Control', 'public, max-age=600, s-maxage=600')
                ->header('ETag', $etag)
                ->header('Vary', 'Accept, Accept-Encoding')
                ->header('X-Cache', cache()->has("car_full:{$id}") ? 'HIT' : 'MISS')
                ->header('X-Cache-Key', "car_full:{$id}")
                ->header('X-Query-Time-ms', $queryTime)
                ->header('X-API-Version', 'v1');

        } catch (Exception $e) {
            return $this->errorResponse('Car not found', 404)
                ->header('X-Cache', 'MISS')
                ->header('X-Query-Time-ms', round((microtime(true) - $startTime) * 1000, 2));
        }
    }

    public function popularMakes(Request $request): JsonResponse
    {
        $startTime = microtime(true);

        try {
            $countryCode = $request->get('country_code');
            $popularMakes = $this->carService->getPopularMakes($countryCode);

            $queryTime = round((microtime(true) - $startTime) * 1000, 2);

            // Generate ETag for cache validation
            $etag = md5(serialize($popularMakes));
            $cacheKey = "popular_makes" . ($countryCode ? ":{$countryCode}" : "");

            // Check If-None-Match header for 304 response
            if ($request->header('If-None-Match') === $etag) {
                return response()->json(null, 304)
                    ->header('Cache-Control', 'public, max-age=1800')
                    ->header('ETag', $etag)
                    ->header('X-Cache', 'HIT-304')
                    ->header('X-Cache-Key', $cacheKey)
                    ->header('X-Query-Time-ms', $queryTime);
            }

            $response = $this->successResponse(
                'Popular makes retrieved successfully',
                $popularMakes
            );

            // Add CDN and cache headers
            return $response
                ->header('Cache-Control', 'public, max-age=1800, s-maxage=1800')
                ->header('ETag', $etag)
                ->header('Vary', 'Accept, Accept-Encoding')
                ->header('X-Cache', cache()->has($cacheKey) ? 'HIT' : 'MISS')
                ->header('X-Cache-Key', $cacheKey)
                ->header('X-Query-Time-ms', $queryTime)
                ->header('X-API-Version', 'v1');

        } catch (Exception $e) {
            return $this->errorResponse('Failed to retrieve popular makes', 500)
                ->header('X-Cache', 'MISS')
                ->header('X-Query-Time-ms', round((microtime(true) - $startTime) * 1000, 2));
        }
    }

}
