<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lead extends Model
{
    use HasFactory;

    protected $fillable = [
        'listing_id',
        'name',
        'email',
        'phone',
        'message',
        'source',
        'ip_address',
        'user_agent',
        'score',
        'status',
        'scoring_data',
        'scored_at',
        'contacted_at',
        'converted_at',
    ];

    protected $casts = [
        'scoring_data' => 'array',
        'scored_at' => 'datetime',
        'contacted_at' => 'datetime',
        'converted_at' => 'datetime',
    ];

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    public function scopeQualified($query)
    {
        return $query->where('status', 'qualified');
    }

    public function scopeByScore($query, int $minScore)
    {
        return $query->where('score', '>=', $minScore);
    }

    public function scopeRecentLeads($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeBySource($query, string $source)
    {
        return $query->where('source', $source);
    }

    public function isQualified(): bool
    {
        return $this->status === 'qualified';
    }

    public function isConverted(): bool
    {
        return $this->status === 'converted';
    }

    public function hasHighScore(): bool
    {
        return $this->score && $this->score >= 70;
    }
}
