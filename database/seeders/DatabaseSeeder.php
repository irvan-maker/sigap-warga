<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if ($this->container->environment('local')) {
            $this->call([
                PilotRegionSeeder::class,
                PilotCitizenSeeder::class,
                DevelopmentAdminSeeder::class,
            ]);
        }
    }
}
