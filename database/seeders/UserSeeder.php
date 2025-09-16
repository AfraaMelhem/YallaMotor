<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'full_name' => 'Test User',
            'email' => 'testuser@example.com',
            'phone' => '+10000000000',
            'password' => Hash::make('password'),
            'birth_date' => '1990-01-01',
            'gender' => 'male',
            'check_in_date' => now(),
            'check_out_date' => now()->addDays(3),
            'actual_check_out_date' => null,
            'stay_duration_days' => 3,
            'language' => 'en',
            'watch_history' => null,
            'last_watched_video_id' => null,
            'is_active' => true,
            'is_vip' => false,
            'qrcode_url' => null,
        ]);
    }
}
