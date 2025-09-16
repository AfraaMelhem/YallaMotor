<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\ListingService;
use App\Traits\BaseResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ListingController extends Controller
{
    use BaseResponse;

    public function __construct(
        private ListingService $listingService
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = min($request->get('per_page', 15), 50);
            $listings = $this->listingService->getFastBrowseListings([], $perPage);

            return $this->successResponse('Listings retrieved successfully', $listings);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve listings', 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $listing = $this->listingService->getListingById($id);
            return $this->successResponse('Listing retrieved successfully', $listing);
        } catch (\Exception $e) {
            return $this->errorResponse('Listing not found', 404);
        }
    }

    public function fastBrowse(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'country' => 'sometimes|string|size:2',
                'make' => 'sometimes|string|max:100',
                'model' => 'sometimes|string|max:100',
                'min_price' => 'sometimes|numeric|min:0',
                'max_price' => 'sometimes|numeric|min:0',
                'min_year' => 'sometimes|integer|min:1900|max:' . (date('Y') + 1),
                'max_year' => 'sometimes|integer|min:1900|max:' . (date('Y') + 1),
                'city' => 'sometimes|string|max:100',
                'sort_by' => 'sometimes|in:price,year,mileage,listed_at',
                'sort_direction' => 'sometimes|in:asc,desc',
                'per_page' => 'sometimes|integer|min:1|max:50',
            ]);

            $perPage = $request->get('per_page', 15);
            $listings = $this->listingService->searchListings($request->all(), $perPage);

            return $this->successResponse('Fast browse results', $listings);
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to perform search', 500);
        }
    }

    public function popularMakes(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'country' => 'sometimes|string|size:2',
            ]);

            $countryCode = $request->get('country');
            $makes = $this->listingService->getPopularMakes($countryCode);

            return $this->successResponse('Popular makes retrieved successfully', $makes);
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve popular makes', 500);
        }
    }

    public function updatePrice(Request $request, int $id): JsonResponse
    {
        try {
            $request->validate([
                'price' => 'required|numeric|min:0',
            ]);

            $listing = $this->listingService->updateListingPrice($id, $request->price);

            return $this->successResponse('Listing price updated successfully', $listing);
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update listing price', 500);
        }
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        try {
            $request->validate([
                'status' => 'required|in:active,sold,hidden',
            ]);

            $listing = $this->listingService->updateListingStatus($id, $request->status);

            return $this->successResponse('Listing status updated successfully', $listing);
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update listing status', 500);
        }
    }
}
