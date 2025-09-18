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
        try {
            $data = $this->prepareListData($request);
            $perPage = min($request->get('per_page', 15), 50);

            $listings = $this->listingService->getPaginatedList($data, $perPage);

            return $this->successResponse(
                'Listings retrieved successfully',
                BaseResource::collection($listings)
            );
        } catch (Exception $e) {
            return $this->errorResponse('Failed to retrieve listings', 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $listing = $this->listingService->show($id);
            return $this->successResponse(
                'Listing retrieved successfully',
                new BaseResource($listing->load(['dealer', 'events' => function($query) {
                    $query->orderBy('created_at', 'desc')->limit(10);
                }]))
            );
        } catch (Exception $e) {
            return $this->errorResponse('Listing not found', 404);
        }
    }

    public function fastBrowse(ListingFastBrowseRequest $request): JsonResponse
    {
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

            return $this->successResponse(
                'Fast browse results',
                BaseResource::collection($listings)->additional([
                    'filters_applied' => $request->validated(),
                    'search_metadata' => [
                        'total_results' => $listings->total(),
                        'search_time' => microtime(true) - LARAVEL_START,
                        'cache_used' => true
                    ]
                ])
            );
        } catch (Exception $e) {
            return $this->errorResponse('Failed to perform search', 500);
        }
    }

    public function popularMakes(Request $request): JsonResponse
    {
        try {
            $request->validate(['country' => 'sometimes|string|size:2']);

            $data = ['filters' => ['country_code' => $request->get('country')]];
            $makes = $this->listingService->getPopularMakes($data);

            return $this->successResponse(
                'Popular makes retrieved successfully',
                [
                    'country' => $request->get('country'),
                    'makes' => $makes,
                    'total_makes' => count($makes),
                    'generated_at' => now()->toISOString()
                ]
            );
        } catch (Exception $e) {
            return $this->errorResponse('Failed to retrieve popular makes', 500);
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
