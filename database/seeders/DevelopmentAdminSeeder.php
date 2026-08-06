<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Enums\VillagePosition;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DevelopmentAdminSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@sigapwarga.test'],
            [
                'name' => 'Development Admin',
                'password' => Hash::make('password'),
                'role' => UserRole::KELURAHAN,
                'position' => VillagePosition::SYSTEM_ADMIN,
                'is_active' => true,
            ],
        );
    }
}
