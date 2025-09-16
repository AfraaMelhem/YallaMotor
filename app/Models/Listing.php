<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Listing extends Model
{
    use HasFactory;

    protected $fillable = [
        'dealer_id',
        'make',
        'model',
        'year',
        'price_cents',
        'mileage_km',
        'country_code',
        'city',
        'status',
        'listed_at',
    ];

    protected $casts = [
        'year' => 'integer',
        'price_cents' => 'integer',
        'mileage_km' => 'integer',
        'listed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function dealer(): BelongsTo
    {
        return $this->belongsTo(Dealer::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(ListingEvent::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeByCountry(Builder $query, string $countryCode): Builder
    {
        return $query->where('country_code', $countryCode);
    }

    public function scopeByMake(Builder $query, string $make): Builder
    {
        return $query->where('make', $make);
    }

    public function scopeByModel(Builder $query, string $model): Builder
    {
        return $query->where('model', $model);
    }

    public function scopePriceRange(Builder $query, int $minPrice = null, int $maxPrice = null): Builder
    {
        if ($minPrice) {
            $query->where('price_cents', '>=', $minPrice);
        }
        if ($maxPrice) {
            $query->where('price_cents', '<=', $maxPrice);
        }
        return $query;
    }

    public function scopeYearRange(Builder $query, int $minYear = null, int $maxYear = null): Builder
    {
        if ($minYear) {
            $query->where('year', '>=', $minYear);
        }
        if ($maxYear) {
            $query->where('year', '<=', $maxYear);
        }
        return $query;
    }

    protected function getPriceAttribute(): float
    {
        return $this->price_cents / 100;
    }
}
