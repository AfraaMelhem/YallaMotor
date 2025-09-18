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
        try {
            $data = $this->prepareListData($request);
            $perPage = min($request->get('per_page', 15), 50);

            $dealers = $this->dealerService->getPaginatedList($data, $perPage);

            return $this->successResponse(
                'Dealers retrieved successfully',
                BaseResource::collection($dealers)
            );
        } catch (Exception $e) {
            return $this->errorResponse('Failed to retrieve dealers', 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $dealer = $this->dealerService->show($id);
            return $this->successResponse(
                'Dealer retrieved successfully',
                new BaseResource($dealer->load(['listings' => function($query) {
                    $query->where('status', 'active')->orderBy('listed_at', 'desc')->limit(5);
                }]))
            );
        } catch (Exception $e) {
            return $this->errorResponse('Dealer not found', 404);
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
                    'dealers' => BaseResource::collection($dealers),
                    'total_dealers' => $dealers->count()
                ]
            );
        } catch (Exception $e) {
            return $this->errorResponse('Failed to retrieve dealers', 500);
        }
    }
}
