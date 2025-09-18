<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateLeadRequest;
use App\Http\Resources\BaseResource;
use App\Jobs\ScoreLeadJob;
use App\Models\Lead;
use App\Services\LeadScoringService;
use App\Traits\BaseResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Exception;

class LeadController extends Controller
{
    use BaseResponse;

    public function __construct(
        protected LeadScoringService $scoringService
    ) {}

    public function store(CreateLeadRequest $request): JsonResponse
    {
        $startTime = microtime(true);

        try {
            // Create the lead with validated data
            $lead = Lead::create($request->validated());

            Log::info('Lead created', [
                'lead_id' => $lead->id,
                'listing_id' => $lead->listing_id,
                'email' => $lead->email,
                'source' => $lead->source,
                'ip' => $lead->ip_address
            ]);

            // Dispatch background scoring job
            ScoreLeadJob::dispatch($lead)->onQueue('scoring');

            $queryTime = round((microtime(true) - $startTime) * 1000, 2);

            $response = $this->successResponse(
                'Lead submitted successfully',
                new BaseResource($lead->load('listing')),
                201
            );

            // Add observability headers
            return $response
                ->header('X-Lead-ID', $lead->id)
                ->header('X-Query-Time-ms', $queryTime)
                ->header('X-API-Version', 'v1')
                ->header('X-Rate-Limit-Remaining', $this->getRateLimitRemaining($request));

        } catch (Exception $e) {
            Log::error('Lead creation failed', [
                'error' => $e->getMessage(),
                'data' => $request->validated(),
                'ip' => $request->ip()
            ]);

            return $this->errorResponse('Failed to submit lead', 500)
                ->header('X-Query-Time-ms', round((microtime(true) - $startTime) * 1000, 2));
        }
    }

    public function index(): JsonResponse
    {
        try {
            $statistics = $this->scoringService->getLeadStatistics();

            return $this->successResponse(
                'Lead statistics retrieved successfully',
                $statistics
            );
        } catch (Exception $e) {
            return $this->errorResponse('Failed to retrieve lead statistics', 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $lead = Lead::with(['listing.dealer'])->findOrFail($id);

            return $this->successResponse(
                'Lead retrieved successfully',
                new BaseResource($lead)
            );
        } catch (Exception $e) {
            return $this->errorResponse('Lead not found', 404);
        }
    }

    private function getRateLimitRemaining($request): int
    {
        $email = $request->input('email', '');
        $ip = $request->ip();
        $key = "leads:{$ip}:{$email}";

        $attempts = \Illuminate\Support\Facades\RateLimiter::attempts($key);
        return max(0, 5 - $attempts);
    }
}
