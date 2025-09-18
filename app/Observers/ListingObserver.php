<?php

namespace App\Observers;

use App\Models\Listing;
use App\Models\ListingEvent;
use App\Services\CacheService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ListingObserver
{
    public function __construct(
        private CacheService $cacheService
    ) {}
    public function created(Listing $listing): void
    {
        // Clear related caches
        $this->clearListingCaches($listing);

        Log::info('New listing created', [
            'listing_id' => $listing->id,
            'dealer_id' => $listing->dealer_id,
            'make' => $listing->make,
            'model' => $listing->model,
            'price' => $listing->price_cents / 100
        ]);
    }

    public function updated(Listing $listing): void
    {
        // Track price changes
        if ($listing->isDirty('price_cents')) {
            $oldPrice = $listing->getOriginal('price_cents');
            $newPrice = $listing->price_cents;

            if ($oldPrice !== $newPrice) {
                ListingEvent::createPriceChangedEvent($listing->id, $oldPrice, $newPrice);

                Log::info('Listing price changed', [
                    'listing_id' => $listing->id,
                    'old_price' => $oldPrice / 100,
                    'new_price' => $newPrice / 100
                ]);
            }
        }

        // Track status changes
        if ($listing->isDirty('status')) {
            $oldStatus = $listing->getOriginal('status');
            $newStatus = $listing->status;

            if ($oldStatus !== $newStatus) {
                ListingEvent::createStatusChangedEvent($listing->id, $oldStatus, $newStatus);

                Log::info('Listing status changed', [
                    'listing_id' => $listing->id,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus
                ]);
            }
        }

        // Clear related caches
        $this->clearListingCaches($listing);
    }

    public function deleted(Listing $listing): void
    {
        // Clear related caches
        $this->clearListingCaches($listing);

        Log::info('Listing deleted', [
            'listing_id' => $listing->id,
            'make' => $listing->make,
            'model' => $listing->model
        ]);
    }

    public function restored(Listing $listing): void
    {
        // Clear related caches
        $this->clearListingCaches($listing);

        Log::info('Listing restored', [
            'listing_id' => $listing->id
        ]);
    }


    private function clearListingCaches(Listing $listing): void
    {
        // Use the cache service for proper tag-based invalidation
        $purgedKeys = $this->cacheService->invalidateListingCaches(
            $listing->id,
            $listing->country_code,
            $listing->dealer_id
        );

        Log::info('Cache invalidated for listing', [
            'listing_id' => $listing->id,
            'country_code' => $listing->country_code,
            'dealer_id' => $listing->dealer_id,
            'purged_keys_count' => count($purgedKeys),
            'purged_keys' => array_slice($purgedKeys, 0, 10) // Log first 10 keys for debugging
        ]);
    }
}
