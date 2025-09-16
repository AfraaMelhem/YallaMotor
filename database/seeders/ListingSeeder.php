<?php

namespace Database\Seeders;

use App\Models\Dealer;
use App\Models\Listing;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ListingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $dealers = Dealer::all();

        if ($dealers->isEmpty()) {
            $this->command->info('No dealers found. Creating dealers first...');
            Dealer::factory()->count(20)->create();
            $dealers = Dealer::all();
        }

        foreach ($dealers as $dealer) {
            Listing::factory()
                ->count(rand(3, 15))
                ->create([
                    'dealer_id' => $dealer->id,
                    'country_code' => $dealer->country_code,
                ]);
        }

        $this->command->info('Created ' . Listing::count() . ' listings for ' . $dealers->count() . ' dealers.');
    }
}
