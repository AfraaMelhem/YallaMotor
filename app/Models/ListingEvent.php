<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ListingEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'listing_id',
        'type',
        'payload',
        'created_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'created_at' => 'datetime',
    ];

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    public static function createPriceChangedEvent(int $listingId, int $oldPrice, int $newPrice): self
    {
        return self::create([
            'listing_id' => $listingId,
            'type' => 'price_changed',
            'payload' => [
                'old_price_cents' => $oldPrice,
                'new_price_cents' => $newPrice,
            ],
            'created_at' => now(),
        ]);
    }

    public static function createStatusChangedEvent(int $listingId, string $oldStatus, string $newStatus): self
    {
        return self::create([
            'listing_id' => $listingId,
            'type' => 'status_changed',
            'payload' => [
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
            ],
            'created_at' => now(),
        ]);
    }
}
