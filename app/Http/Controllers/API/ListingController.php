<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\ListingFastBrowseRequest;
use App\Http\Requests\UpdateListingPriceRequest;
use App\Http\Requests\UpdateListingStatusRequest;
use App\Http\Resources\BaseResource;
use App\Services\ListingService;
use App\Traits\BaseResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;

class ListingController extends Controller
{
    use BaseResponse;

    public function __construct(
        protected ListingService $listingService
    ) {}

    public function getPaginatedList(Request $request): JsonResponse
    {
        $startTime = microtime(true);

        try {
            $data = $this->prepareListData($request);
            $perPage = min($request->get('per_page', 15), 50);

            $listings = $this->listingService->getPaginatedList($data, $perPage);

            $queryTime = round((microtime(true) - $startTime) * 1000, 2);

            // Generate ETag for cache validation
            $etag = md5(serialize($listings->items()) . $listings->total() . serialize($data));
            $cacheKey = 'listings_list:' . md5(serialize($data) . $perPage);

            // Check If-None-Match header for 304 response
            if ($request->header('If-None-Match') === $etag) {
                return response()->json(null, 304)
                    ->header('Cache-Control', 'public, max-age=600')
                    ->header('ETag', $etag)
                    ->header('X-Cache', 'HIT-304')
                    ->header('X-Cache-Key', $cacheKey)
                    ->header('X-Query-Time-ms', $queryTime);
            }

            // Structure response data manually
            $responseData = [
                'data' => BaseResource::collection($listings)->resolve(),
                'meta' => [
                    'pagination' => [
                        'current_page' => $listings->currentPage(),
                        'per_page' => $listings->perPage(),
                        'total' => $listings->total(),
                        'last_page' => $listings->lastPage(),
                        'from' => $listings->firstItem(),
                        'to' => $listings->lastItem(),
                    ]
                ]
            ];

            $response = $this->successResponse(
                'Listings retrieved successfully',
                $responseData
            );

            // Add CDN and cache headers
            return $response
                ->header('Cache-Control', 'public, max-age=600, s-maxage=600')
                ->header('ETag', $etag)
                ->header('Vary', 'Accept, Accept-Encoding')
                ->header('X-Cache', cache()->has($cacheKey) ? 'HIT' : 'MISS')
                ->header('X-Cache-Key', $cacheKey)
                ->header('X-Query-Time-ms', $queryTime)
                ->header('X-Total-Listings', $listings->total())
                ->header('X-API-Version', 'v1');

        } catch (Exception $e) {
            return $this->errorResponse('Failed to retrieve listings', 500)
                ->header('X-Cache', 'MISS')
                ->header('X-Query-Time-ms', round((microtime(true) - $startTime) * 1000, 2));
        }
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $startTime = microtime(true);

        try {
            // Get listing with full caching (including relationships)
            $listing = $this->listingService->showWithRelations($id);

            $queryTime = round((microtime(true) - $startTime) * 1000, 2);

            // Generate ETag for cache validation
            $etag = md5(serialize($listing->toArray()));

            // Check If-None-Match header for 304 response
            if ($request->header('If-None-Match') === $etag) {
                return response()->json(null, 304)
                    ->header('Cache-Control', 'public, max-age=600')
                    ->header('ETag', $etag)
                    ->header('X-Cache', 'HIT-304')
                    ->header('X-Cache-Key', "listing_full:{$id}")
                    ->header('X-Query-Time-ms', $queryTime);
            }

            $response = $this->successResponse(
                'Listing retrieved successfully',
                new BaseResource($listing)
            );

            // Add CDN and cache headers
            $cacheKey = "listing_full:{$id}";
            return $response
                ->header('Cache-Control', 'public, max-age=600, s-maxage=600')
                ->header('ETag', $etag)
                ->header('Vary', 'Accept, Accept-Encoding')
                ->header('X-Cache', cache()->has($cacheKey) ? 'HIT' : 'MISS')
                ->header('X-Cache-Key', $cacheKey)
                ->header('X-Query-Time-ms', $queryTime)
                ->header('X-API-Version', 'v1');

        } catch (Exception $e) {
            return $this->errorResponse('Listing not found', 404)
                ->header('X-Query-Time-ms', round((microtime(true) - $startTime) * 1000, 2));
        }
    }

    public function fastBrowse(ListingFastBrowseRequest $request): JsonResponse
    {
        $startTime = microtime(true);

        try {
            // Prepare data for the service using the command pattern
            $data = [
                'filters' => $request->validated(),
                'search' => $request->input('search'),
                'sort' => [
                    $request->validated('sort_by', 'listed_at') => $request->validated('sort_direction', 'desc')
                ]
            ];
            $perPage = $request->validated('per_page', 15);

            $listings = $this->listingService->getFastBrowseListings($data, $perPage);

            $queryTime = round((microtime(true) - $startTime) * 1000, 2);

            // Generate ETag for cache validation
            $etag = md5(serialize($listings->items()) . $listings->total());

            // Check If-None-Match header for 304 response
            if ($request->header('If-None-Match') === $etag) {
                return response()->json(null, 304)
                    ->header('Cache-Control', 'public, max-age=300')
                    ->header('ETag', $etag)
                    ->header('X-Cache', 'HIT-304')
                    ->header('X-Query-Time-ms', $queryTime);
            }

            // Structure response data manually
            $responseData = [
                'data' => BaseResource::collection($listings)->resolve(),
                'meta' => [
                    'pagination' => [
                        'current_page' => $listings->currentPage(),
                        'per_page' => $listings->perPage(),
                        'total' => $listings->total(),
                        'last_page' => $listings->lastPage(),
                        'from' => $listings->firstItem(),
                        'to' => $listings->lastItem(),
                    ],
                    'filters_applied' => $request->validated(),
                    'search_metadata' => [
                        'total_results' => $listings->total(),
                        'search_time' => $queryTime,
                        'cache_used' => true
                    ]
                ]
            ];

            $response = $this->successResponse(
                'Fast browse results',
                $responseData
            );

            // Add CDN and cache headers
            return $response
                ->header('Cache-Control', 'public, max-age=300, s-maxage=300')
                ->header('ETag', $etag)
                ->header('Vary', 'Accept, Accept-Encoding')
                ->header('X-Cache', 'HIT') // Assuming cache service is used
                ->header('X-Query-Time-ms', $queryTime)
                ->header('X-API-Version', 'v1');

        } catch (Exception $e) {
            return $this->errorResponse('Failed to perform search', 500)
                ->header('X-Query-Time-ms', round((microtime(true) - $startTime) * 1000, 2));
        }
    }

    public function popularMakes(Request $request): JsonResponse
    {
        $startTime = microtime(true);

        try {
            $request->validate([
                'country' => 'sometimes|string|size:2',
                'country_code' => 'sometimes|string|size:2'
            ]);

            // Support both 'country' and 'country_code' parameters
            $countryCode = $request->get('country_code') ?: $request->get('country');
            $data = ['filters' => ['country_code' => $countryCode]];
            $makes = $this->listingService->getPopularMakes($data);

            $queryTime = round((microtime(true) - $startTime) * 1000, 2);

            $responseData = [
                'country_code' => $countryCode ? strtoupper($countryCode) : null,
                'makes' => $makes,
                'total_makes' => count($makes),
                'generated_at' => now()->toISOString()
            ];

            // Generate ETag for cache validation
            $etag = md5(serialize($responseData));
            $cacheKey = "listings_popular_makes" . ($countryCode ? ":{$countryCode}" : "");

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
                $responseData
            );

            // Add CDN and cache headers
            return $response
                ->header('Cache-Control', 'public, max-age=1800, s-maxage=1800')
                ->header('ETag', $etag)
                ->header('Vary', 'Accept, Accept-Encoding')
                ->header('X-Cache', cache()->has($cacheKey) ? 'HIT' : 'MISS')
                ->header('X-Cache-Key', $cacheKey)
                ->header('X-Query-Time-ms', $queryTime)
                ->header('X-Total-Makes', count($makes))
                ->header('X-API-Version', 'v1');

        } catch (Exception $e) {
            return $this->errorResponse('Failed to retrieve popular makes', 500)
                ->header('X-Cache', 'MISS')
                ->header('X-Query-Time-ms', round((microtime(true) - $startTime) * 1000, 2));
        }
    }

    public function updatePrice(UpdateListingPriceRequest $request, int $id): JsonResponse
    {
        $data = $request->validated();

        try {
            $listing = $this->listingService->updatePrice($id, $data);

            return $this->successResponse(
                'Listing price updated successfully',
                new BaseResource($listing->load('dealer'))
            );
        } catch (Exception $e) {
            return $this->errorResponse('Failed to update listing price', 500);
        }
    }

    public function updateStatus(UpdateListingStatusRequest $request, int $id): JsonResponse
    {
        $data = $request->validated();

        try {
            $listing = $this->listingService->updateStatus($id, $data);

            return $this->successResponse(
                'Listing status updated successfully',
                new BaseResource($listing->load('dealer'))
            );
        } catch (Exception $e) {
            return $this->errorResponse('Failed to update listing status', 500);
        }
    }
}
