<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\CarFilterRequest;
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

    public function index(CarFilterRequest $request): JsonResponse
    {
        $startTime = microtime(true);

        try {
            // Prepare data using the command pattern
            $data = [
                'filters' => $request->validated(),
                'sort' => [
                    $request->validated('sort_by', 'listed_at') => $request->validated('sort_direction', 'desc')
                ]
            ];

            $perPage = $request->validated('per_page', 20);
            $includeFacets = $request->validated('include_facets', false);

            $result = $this->carService->getFilteredCars($data, $perPage, $includeFacets);

            $queryTime = round((microtime(true) - $startTime) * 1000, 2);

            // Generate ETag for cache validation
            $etag = md5(serialize($result['cars']->items()) . $result['cars']->total());

            // Check If-None-Match header for 304 response
            if ($request->header('If-None-Match') === $etag) {
                return response()->json(null, 304)
                    ->header('Cache-Control', 'public, max-age=300')
                    ->header('ETag', $etag)
                    ->header('X-Cache', 'HIT-304')
                    ->header('X-Cache-Key', $result['cache_key'])
                    ->header('X-Query-Time-ms', $queryTime);
            }

            $response = $this->successResponse(
                'Cars retrieved successfully',
                BaseResource::collection($result['cars'])->additional([
                    'meta' => [
                        'filters_applied' => $request->validated(),
                        'total_results' => $result['cars']->total(),
                        'page' => $result['cars']->currentPage(),
                        'per_page' => $result['cars']->perPage(),
                        'last_page' => $result['cars']->lastPage(),
                        'query_time_ms' => $queryTime,
                    ],
                    'facets' => $includeFacets ? $result['facets'] : null,
                ]),
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
            $car = $this->carService->show($id);

            // Generate ETag for cache validation
            $etag = md5(serialize($car->toArray()));

            // Check If-None-Match header for 304 response
            if ($request->header('If-None-Match') === $etag) {
                return response()->json(null, 304)
                    ->header('Cache-Control', 'public, max-age=600')
                    ->header('ETag', $etag)
                    ->header('X-Cache', 'HIT-304')
                    ->header('X-Cache-Key', "car:{$id}")
                    ->header('X-Query-Time-ms', round((microtime(true) - $startTime) * 1000, 2));
            }

            $queryTime = round((microtime(true) - $startTime) * 1000, 2);

            $response = $this->successResponse(
                'Car retrieved successfully',
                new BaseResource($car->load([
                    'dealer',
                    'events' => function($query) {
                        $query->orderBy('created_at', 'desc')->limit(5);
                    }
                ]))
            );

            // Add CDN and observability headers
            return $response
                ->header('Cache-Control', 'public, max-age=600, s-maxage=600')
                ->header('ETag', $etag)
                ->header('Vary', 'Accept, Accept-Encoding')
                ->header('X-Cache', cache()->has("car:{$id}") ? 'HIT' : 'MISS')
                ->header('X-Cache-Key', "car:{$id}")
                ->header('X-Query-Time-ms', $queryTime)
                ->header('X-API-Version', 'v1');

        } catch (Exception $e) {
            return $this->errorResponse('Car not found', 404)
                ->header('X-Cache', 'MISS')
                ->header('X-Query-Time-ms', round((microtime(true) - $startTime) * 1000, 2));
        }
    }

    public function testFilters(CarFilterRequest $request): JsonResponse
    {
        $filters = $request->validated();

        // Show what filters were received
        $filterDebug = [
            'received_filters' => $filters,
            'available_filter_methods' => [],
            'sql_query' => null,
            'results_count' => 0,
            'sample_results' => []
        ];

        // Check which filter methods exist on the Listing model
        $listing = new \App\Models\Listing();
        foreach ($filters as $key => $value) {
            $method = 'filterBy' . ucfirst(str_replace('_', '', ucwords($key, '_')));
            $filterDebug['available_filter_methods'][$key] = [
                'method_name' => $method,
                'exists' => method_exists($listing, $method),
                'value' => $value
            ];
        }

        try {
            // Test the actual filtering
            $query = $this->carService->getFilteredCars(['filters' => $filters], 5, false);
            $results = $query['cars'];

            $filterDebug['sql_query'] = $results->toSql();
            $filterDebug['results_count'] = $results->total();
            $filterDebug['sample_results'] = $results->items();

        } catch (\Exception $e) {
            $filterDebug['error'] = $e->getMessage();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Filter test results',
            'data' => $filterDebug
        ]);
    }

}
