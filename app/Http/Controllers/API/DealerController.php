<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDealerRequest;
use App\Http\Requests\UpdateDealerRequest;
use App\Http\Resources\BaseResource;
use App\Services\DealerService;
use App\Traits\BaseResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;

class DealerController extends Controller
{
    use BaseResponse;

    public function __construct(
        protected DealerService $dealerService
    ) {}

    public function getPaginatedList(Request $request): JsonResponse
    {
        $startTime = microtime(true);

        try {
            $data = $this->prepareListData($request);
            $perPage = min($request->get('per_page', 15), 50);

            $dealers = $this->dealerService->getPaginatedList($data, $perPage);

            $queryTime = round((microtime(true) - $startTime) * 1000, 2);

            // Generate ETag for cache validation
            $etag = md5(serialize($dealers->items()) . $dealers->total() . serialize($data));
            $cacheKey = 'dealers_list:' . md5(serialize($data) . $perPage);

            // Check If-None-Match header for 304 response
            if ($request->header('If-None-Match') === $etag) {
                return response()->json(null, 304)
                    ->header('Cache-Control', 'public, max-age=600')
                    ->header('ETag', $etag)
                    ->header('X-Cache', 'HIT-304')
                    ->header('X-Cache-Key', $cacheKey)
                    ->header('X-Query-Time-ms', $queryTime);
            }

            $responseData = [
                'data' => BaseResource::collection($dealers)->resolve(),
                'meta' => [
                    'pagination' => [
                        'current_page' => $dealers->currentPage(),
                        'per_page' => $dealers->perPage(),
                        'total' => $dealers->total(),
                        'last_page' => $dealers->lastPage(),
                        'from' => $dealers->firstItem(),
                        'to' => $dealers->lastItem(),
                    ]
                ]
            ];

            $response = $this->successResponse(
                'Dealers retrieved successfully',
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
                ->header('X-Total-Dealers', $dealers->total())
                ->header('X-API-Version', 'v1');

        } catch (Exception $e) {
            return $this->errorResponse('Failed to retrieve dealers', 500)
                ->header('X-Cache', 'MISS')
                ->header('X-Query-Time-ms', round((microtime(true) - $startTime) * 1000, 2));
        }
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $startTime = microtime(true);

        try {
            $dealer = $this->dealerService->showWithRelations($id);

            $queryTime = round((microtime(true) - $startTime) * 1000, 2);

            // Generate ETag for cache validation
            $etag = md5(serialize($dealer->toArray()));

            // Check If-None-Match header for 304 response
            if ($request->header('If-None-Match') === $etag) {
                return response()->json(null, 304)
                    ->header('Cache-Control', 'public, max-age=3600')
                    ->header('ETag', $etag)
                    ->header('X-Cache', 'HIT-304')
                    ->header('X-Cache-Key', "dealer_full:{$id}")
                    ->header('X-Query-Time-ms', $queryTime);
            }

            $response = $this->successResponse(
                'Dealer retrieved successfully',
                new BaseResource($dealer)
            );

            // Add CDN and cache headers
            $cacheKey = "dealer_full:{$id}";
            return $response
                ->header('Cache-Control', 'public, max-age=3600, s-maxage=3600')
                ->header('ETag', $etag)
                ->header('Vary', 'Accept, Accept-Encoding')
                ->header('X-Cache', cache()->has($cacheKey) ? 'HIT' : 'MISS')
                ->header('X-Cache-Key', $cacheKey)
                ->header('X-Query-Time-ms', $queryTime)
                ->header('X-API-Version', 'v1');

        } catch (Exception $e) {
            return $this->errorResponse('Dealer not found', 404)
                ->header('X-Query-Time-ms', round((microtime(true) - $startTime) * 1000, 2));
        }
    }

    public function create(StoreDealerRequest $request): JsonResponse
    {
        $data = $request->validated();

        try {
            $dealer = $this->dealerService->create($data);

            return $this->successResponse(
                'Dealer created successfully',
                new BaseResource($dealer),
                201
            );
        } catch (Exception $e) {
            return $this->errorResponse('Failed to create dealer', 500);
        }
    }

    public function update(UpdateDealerRequest $request, int $id): JsonResponse
    {
        $data = $request->validated();

        try {
            $dealer = $this->dealerService->update($id, $data);

            return $this->successResponse(
                'Dealer updated successfully',
                new BaseResource($dealer)
            );
        } catch (Exception $e) {
            return $this->errorResponse('Failed to update dealer', 500);
        }
    }

    public function delete(int $id): JsonResponse
    {
        try {
            $this->dealerService->delete($id);
            return $this->successResponse('Dealer deleted successfully');
        } catch (Exception $e) {
            return $this->errorResponse('Failed to delete dealer', 500);
        }
    }

    public function byCountry(string $countryCode): JsonResponse
    {
        try {
            if (strlen($countryCode) !== 2) {
                return $this->errorResponse('Invalid country code format', 400);
            }

            $dealers = $this->dealerService->getDealersByCountry(strtoupper($countryCode));

            return $this->successResponse(
                'Dealers retrieved successfully',
                [
                    'country_code' => strtoupper($countryCode),
                    'dealers' => BaseResource::collection($dealers)->resolve(),
                    'total_dealers' => $dealers->count()
                ]
            );
        } catch (Exception $e) {
            return $this->errorResponse('Failed to retrieve dealers', 500);
        }
    }
}
