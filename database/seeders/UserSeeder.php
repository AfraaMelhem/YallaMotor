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
        ]);
    }
}
