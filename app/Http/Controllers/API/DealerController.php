<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\DealerService;
use App\Traits\BaseResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DealerController extends Controller
{
    use BaseResponse;

    public function __construct(
        private DealerService $dealerService
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = min($request->get('per_page', 15), 50);
            $dealers = $this->dealerService->getAllDealers($perPage);

            return $this->successResponse('Dealers retrieved successfully', $dealers);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve dealers', 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $dealer = $this->dealerService->getDealerById($id);
            return $this->successResponse('Dealer retrieved successfully', $dealer);
        } catch (\Exception $e) {
            return $this->errorResponse('Dealer not found', 404);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'country_code' => 'required|string|size:2',
            ]);

            $dealer = $this->dealerService->createDealer($request->only(['name', 'country_code']));

            return $this->successResponse('Dealer created successfully', $dealer, 201);
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create dealer', 500);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $request->validate([
                'name' => 'sometimes|string|max:255',
                'country_code' => 'sometimes|string|size:2',
            ]);

            $dealer = $this->dealerService->updateDealer($id, $request->only(['name', 'country_code']));

            return $this->successResponse('Dealer updated successfully', $dealer);
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update dealer', 500);
        }
    }

    public function byCountry(Request $request, string $countryCode): JsonResponse
    {
        try {
            if (strlen($countryCode) !== 2) {
                return $this->errorResponse('Invalid country code format', 400);
            }

            $dealers = $this->dealerService->getDealersByCountry(strtoupper($countryCode));

            return $this->successResponse('Dealers retrieved successfully', $dealers);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve dealers', 500);
        }
    }
}
