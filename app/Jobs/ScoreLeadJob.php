<?php

namespace App\Jobs;

use App\Models\Lead;
use App\Services\LeadScoringService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Exception;

class ScoreLeadJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public $tries = 3;
    public $timeout = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Lead $lead
    ) {}

    /**
     * Execute the job.
     */
    public function handle(LeadScoringService $scoringService): void
    {
        try {
            Log::info('Starting lead scoring', ['lead_id' => $this->lead->id]);

            $scoringResult = $scoringService->scoreLead($this->lead);

            $this->lead->update([
                'score' => $scoringResult['score'],
                'status' => $scoringResult['suggested_status'],
                'scoring_data' => $scoringResult['scoring_data'],
                'scored_at' => now(),
            ]);

            Log::info('Lead scoring completed', [
                'lead_id' => $this->lead->id,
                'score' => $scoringResult['score'],
                'status' => $scoringResult['suggested_status']
            ]);

        } catch (Exception $e) {
            Log::error('Lead scoring failed', [
                'lead_id' => $this->lead->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Handle job failure.
     */
    public function failed(Exception $exception): void
    {
        Log::error('Lead scoring job failed permanently', [
            'lead_id' => $this->lead->id,
            'error' => $exception->getMessage()
        ]);

        // Mark lead as failed scoring but keep it in system
        $this->lead->update([
            'scoring_data' => [
                'error' => 'Scoring failed',
                'error_message' => $exception->getMessage(),
                'failed_at' => now()->toISOString()
            ]
        ]);
    }
}
