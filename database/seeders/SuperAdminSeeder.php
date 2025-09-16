<?php

namespace Database\Seeders;

use App\Models\Admin;
use Spatie\Permission\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'admin']);

        $admin = Admin::create([
            'name' => 'Master Admin',
            'email' => 'admin@test.com',
            'password' => Hash::make('password'),
            'is_super_admin' => true,
        ]);

        $admin->assignRole('super-admin');
    }
}
