<?php

namespace Database\Factories;

use App\Models\Lead;
use App\Models\Listing;
use Illuminate\Database\Eloquent\Factories\Factory;

class LeadFactory extends Factory
{
    protected $model = Lead::class;

    public function definition(): array
    {
        return [
            'listing_id' => Listing::factory(),
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'message' => $this->faker->optional()->text(200),
            'source' => $this->faker->randomElement(['api', 'website', 'mobile', 'social']),
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
            'score' => $this->faker->optional()->numberBetween(0, 100),
            'status' => $this->faker->randomElement(['new', 'qualified', 'contacted', 'converted', 'lost']),
            'scoring_data' => null,
            'scored_at' => $this->faker->optional()->dateTimeBetween('-1 month', 'now'),
            'contacted_at' => $this->faker->optional()->dateTimeBetween('-1 month', 'now'),
            'converted_at' => $this->faker->optional()->dateTimeBetween('-1 month', 'now'),
        ];
    }

    public function qualified(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'qualified',
                'score' => $this->faker->numberBetween(70, 100),
                'scored_at' => now(),
            ];
        });
    }

    public function converted(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'converted',
                'score' => $this->faker->numberBetween(80, 100),
                'scored_at' => now(),
                'contacted_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
                'converted_at' => now(),
            ];
        });
    }

    public function withListing(int $listingId): Factory
    {
        return $this->state(function (array $attributes) use ($listingId) {
            return [
                'listing_id' => $listingId,
            ];
        });
    }
}