<?php

namespace App\Services;

use App\Models\Lead;
use App\Services\CacheService;
use Illuminate\Support\Facades\Cache;

class LeadService
{
    public function __construct(
        private CacheService $cacheService
    ) {}

    public function show(int $id)
    {
        $cacheKey = "lead:{$id}";
        $tags = ["lead:{$id}"];

        return $this->cacheService->remember($cacheKey, $tags, 600, function () use ($id) {
            return Lead::findOrFail($id);
        });
    }

    public function showWithRelations(int $id)
    {
        $cacheKey = "lead_full:{$id}";
        $tags = ["lead:{$id}"];

        return $this->cacheService->remember($cacheKey, $tags, 600, function () use ($id) {
            return Lead::with(['listing.dealer'])->findOrFail($id);
        });
    }

    public function create(array $data)
    {
        $lead = Lead::create($data);

        // Clear relevant caches
        Cache::tags(['lead_statistics'])->flush();

        return $lead;
    }

    public function update(int $id, array $data)
    {
        $lead = Lead::findOrFail($id);
        $lead->update($data);

        // Clear relevant caches
        Cache::forget("lead:{$id}");
        Cache::forget("lead_full:{$id}");
        Cache::tags(['lead_statistics'])->flush();

        return $lead;
    }

    public function delete(int $id): bool
    {
        $lead = Lead::findOrFail($id);
        $result = $lead->delete();

        // Clear relevant caches
        Cache::forget("lead:{$id}");
        Cache::forget("lead_full:{$id}");
        Cache::tags(['lead_statistics'])->flush();

        return $result;
    }

    public function getLeadStatistics(): array
    {
        $cacheKey = 'lead_statistics';
        $tags = ['lead_statistics'];

        return $this->cacheService->remember($cacheKey, $tags, 1800, function () {
            return [
                'total_leads' => Lead::count(),
                'today_leads' => Lead::whereDate('created_at', today())->count(),
                'average_score' => round(Lead::whereNotNull('score')->avg('score'), 2),
                'score_distribution' => [
                    'high' => Lead::where('score', '>=', 80)->count(),
                    'medium' => Lead::whereBetween('score', [50, 79])->count(),
                    'low' => Lead::where('score', '<', 50)->count(),
                ],
                'leads_by_source' => Lead::selectRaw('source, COUNT(*) as count')
                    ->groupBy('source')
                    ->pluck('count', 'source')
                    ->toArray(),
                'leads_by_status' => Lead::selectRaw('status, COUNT(*) as count')
                    ->groupBy('status')
                    ->pluck('count', 'status')
                    ->toArray(),
            ];
        });
    }

    public function getLeadsByListing(int $listingId, int $perPage = 15)
    {
        $cacheKey = "leads_by_listing:{$listingId}:page:{$perPage}";
        $tags = ['leads_by_listing', "listing:{$listingId}"];

        return $this->cacheService->remember($cacheKey, $tags, 300, function () use ($listingId, $perPage) {
            return Lead::where('listing_id', $listingId)
                ->with(['listing'])
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);
        });
    }
}