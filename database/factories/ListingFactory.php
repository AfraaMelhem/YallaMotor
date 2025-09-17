<?php

namespace Database\Factories;

use App\Models\Dealer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Listing>
 */
class ListingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $makes = ['Toyota', 'Honda', 'Ford', 'BMW', 'Mercedes', 'Audi', 'Nissan', 'Hyundai', 'Kia', 'Volkswagen'];
        $models = [
            'Toyota' => ['Camry', 'Corolla', 'Prius', 'Highlander', 'Rav4'],
            'Honda' => ['Accord', 'Civic', 'CR-V', 'Pilot', 'Odyssey'],
            'Ford' => ['F-150', 'Mustang', 'Explorer', 'Fusion', 'Escape'],
            'BMW' => ['3 Series', '5 Series', 'X3', 'X5', 'i3'],
            'Mercedes' => ['C-Class', 'E-Class', 'GLE', 'CLA', 'A-Class'],
            'Audi' => ['A4', 'A6', 'Q5', 'Q7', 'A3'],
            'Nissan' => ['Altima', 'Sentra', 'Rogue', 'Pathfinder', 'Leaf'],
            'Hyundai' => ['Elantra', 'Sonata', 'Tucson', 'Santa Fe', 'Ioniq'],
            'Kia' => ['Optima', 'Forte', 'Sorento', 'Sportage', 'Soul'],
            'Volkswagen' => ['Jetta', 'Passat', 'Golf', 'Tiguan', 'Atlas'],
        ];

        $cities = ['New York', 'Los Angeles', 'Chicago', 'Houston', 'Phoenix', 'Philadelphia', 'San Antonio', 'San Diego', 'Dallas', 'San Jose', 'London', 'Berlin', 'Paris', 'Dubai', 'Riyadh'];

        $make = $this->faker->randomElement($makes);
        $model = $this->faker->randomElement($models[$make]);

        return [
            'dealer_id' => Dealer::factory(),
            'make' => $make,
            'model' => $model,
            'year' => $this->faker->numberBetween(2010, 2024),
            'price_cents' => $this->faker->numberBetween(500000, 8000000), // $5,000 to $80,000
            'mileage_km' => $this->faker->numberBetween(0, 200000),
            'country_code' => $this->faker->randomElement(['US', 'CA', 'GB', 'DE', 'FR', 'AU', 'AE', 'SA']),
            'city' => $this->faker->randomElement($cities),
            'status' => $this->faker->randomElement(['active', 'active', 'active', 'active', 'sold', 'hidden']), // Weighted towards active
            'listed_at' => $this->faker->dateTimeBetween('-6 months', 'now'),
        ];
    }
}
