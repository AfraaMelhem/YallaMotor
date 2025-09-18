<?php

namespace App\Services;

use App\Models\Lead;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class LeadScoringService
{
    public function scoreLead(Lead $lead): array
    {
        $score = 0;
        $scoringData = [];

        // Email validation scoring (30 points max)
        $emailScore = $this->scoreEmail($lead->email);
        $score += $emailScore['score'];
        $scoringData['email'] = $emailScore;

        // Phone validation scoring (20 points max)
        if ($lead->phone) {
            $phoneScore = $this->scorePhone($lead->phone);
            $score += $phoneScore['score'];
            $scoringData['phone'] = $phoneScore;
        }

        // Message quality scoring (20 points max)
        if ($lead->message) {
            $messageScore = $this->scoreMessage($lead->message);
            $score += $messageScore['score'];
            $scoringData['message'] = $messageScore;
        }

        // Listing interaction scoring (15 points max)
        $listingScore = $this->scoreListingInteraction($lead);
        $score += $listingScore['score'];
        $scoringData['listing'] = $listingScore;

        // Behavioral scoring (15 points max)
        $behaviorScore = $this->scoreBehavior($lead);
        $score += $behaviorScore['score'];
        $scoringData['behavior'] = $behaviorScore;

        // Determine suggested status based on score
        $suggestedStatus = $this->determineSuggestedStatus($score);

        return [
            'score' => $score,
            'suggested_status' => $suggestedStatus,
            'scoring_data' => $scoringData,
            'scored_at' => now()->toISOString()
        ];
    }

    private function scoreEmail(string $email): array
    {
        $score = 0;
        $details = [];

        // Basic email validation (5 points)
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $score += 5;
            $details['valid_format'] = true;
        } else {
            $details['valid_format'] = false;
            return ['score' => 0, 'details' => $details];
        }

        // Domain scoring (25 points max)
        $domain = substr(strrchr($email, "@"), 1);
        $domainScore = $this->scoreDomain($domain);
        $score += $domainScore['score'];
        $details['domain'] = $domainScore;

        return ['score' => $score, 'details' => $details];
    }

    private function scoreDomain(string $domain): array
    {
        $score = 0;
        $details = [];

        // Corporate domains get higher scores
        $corporateDomains = [
            'gmail.com' => 15,
            'outlook.com' => 15,
            'hotmail.com' => 10,
            'yahoo.com' => 10,
            'icloud.com' => 15,
        ];

        if (isset($corporateDomains[$domain])) {
            $score = $corporateDomains[$domain];
            $details['type'] = 'known_provider';
        } else {
            // Unknown domains get moderate score but check if they look corporate
            if ($this->looksCorporate($domain)) {
                $score = 20;
                $details['type'] = 'corporate';
            } else {
                $score = 5;
                $details['type'] = 'unknown';
            }
        }

        $details['domain'] = $domain;
        return ['score' => $score, 'details' => $details];
    }

    private function looksCorporate(string $domain): bool
    {
        // Simple heuristics for corporate domains
        $parts = explode('.', $domain);
        if (count($parts) >= 2) {
            $name = $parts[0];
            // Corporate names are usually longer and don't contain numbers
            return strlen($name) > 3 && !preg_match('/\d/', $name);
        }
        return false;
    }

    private function scorePhone(string $phone): array
    {
        $score = 0;
        $details = [];

        // Clean phone number
        $cleaned = preg_replace('/[^\d+]/', '', $phone);
        $details['cleaned'] = $cleaned;

        // Basic format validation (10 points)
        if (strlen($cleaned) >= 10) {
            $score += 10;
            $details['valid_length'] = true;
        } else {
            $details['valid_length'] = false;
        }

        // International format bonus (10 points)
        if (str_starts_with($cleaned, '+')) {
            $score += 10;
            $details['international_format'] = true;
        }

        return ['score' => $score, 'details' => $details];
    }

    private function scoreMessage(string $message): array
    {
        $score = 0;
        $details = [];

        $wordCount = str_word_count($message);
        $details['word_count'] = $wordCount;

        // Message length scoring
        if ($wordCount >= 10) {
            $score += 15;
            $details['length_score'] = 'detailed';
        } elseif ($wordCount >= 5) {
            $score += 10;
            $details['length_score'] = 'moderate';
        } else {
            $score += 5;
            $details['length_score'] = 'brief';
        }

        // Intent indicators (5 points max)
        $intentKeywords = ['buy', 'purchase', 'interested', 'financing', 'loan', 'test drive', 'schedule', 'visit'];
        $intentCount = 0;
        foreach ($intentKeywords as $keyword) {
            if (stripos($message, $keyword) !== false) {
                $intentCount++;
            }
        }

        if ($intentCount > 0) {
            $score += min(5, $intentCount * 2);
            $details['intent_indicators'] = $intentCount;
        }

        return ['score' => $score, 'details' => $details];
    }

    private function scoreListingInteraction(Lead $lead): array
    {
        $score = 0;
        $details = [];

        // Load listing with related data
        $listing = $lead->listing()->with('dealer')->first();
        $details['listing_id'] = $listing->id;
        $details['listing_price'] = $listing->price_cents / 100;

        // High-value listings get bonus points (10 points max)
        $priceScore = min(10, ($listing->price_cents / 100) / 10000); // $10k = 1 point, $100k = 10 points
        $score += $priceScore;
        $details['price_score'] = $priceScore;

        // Recent listings get bonus (5 points max)
        $daysSinceListed = now()->diffInDays($listing->listed_at);
        if ($daysSinceListed <= 7) {
            $score += 5;
            $details['recent_listing'] = true;
        } elseif ($daysSinceListed <= 30) {
            $score += 2;
            $details['recent_listing'] = false;
        }

        return ['score' => $score, 'details' => $details];
    }

    private function scoreBehavior(Lead $lead): array
    {
        $score = 0;
        $details = [];

        // Time-based scoring (5 points max)
        $hour = now()->hour;
        if ($hour >= 9 && $hour <= 17) {
            $score += 5; // Business hours
            $details['submitted_during_business_hours'] = true;
        } else {
            $score += 2; // Outside business hours
            $details['submitted_during_business_hours'] = false;
        }

        // Source scoring (5 points max)
        $sourceScores = [
            'api' => 3,
            'website' => 5,
            'mobile' => 4,
            'social' => 2
        ];

        $sourceScore = $sourceScores[$lead->source] ?? 1;
        $score += $sourceScore;
        $details['source_score'] = $sourceScore;

        // Duplicate lead check (5 points penalty if duplicate)
        $duplicateCount = Lead::where('email', $lead->email)
            ->where('id', '!=', $lead->id)
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        if ($duplicateCount > 0) {
            $score -= 5;
            $details['recent_duplicates'] = $duplicateCount;
        } else {
            $score += 5;
            $details['recent_duplicates'] = 0;
        }

        return ['score' => $score, 'details' => $details];
    }

    private function determineSuggestedStatus(int $score): string
    {
        if ($score >= 70) {
            return 'qualified';
        } elseif ($score >= 40) {
            return 'new';
        } else {
            return 'new'; // Even low scores start as new for manual review
        }
    }

    public function getLeadStatistics(): array
    {
        return Cache::remember('lead_statistics', 1800, function () {
            return [
                'total_leads' => Lead::count(),
                'qualified_leads' => Lead::qualified()->count(),
                'converted_leads' => Lead::where('status', 'converted')->count(),
                'average_score' => round(Lead::whereNotNull('score')->avg('score'), 2),
                'leads_by_status' => Lead::selectRaw('status, COUNT(*) as count')
                    ->groupBy('status')
                    ->pluck('count', 'status')
                    ->toArray(),
                'leads_by_source' => Lead::selectRaw('source, COUNT(*) as count')
                    ->groupBy('source')
                    ->pluck('count', 'source')
                    ->toArray(),
            ];
        });
    }
}