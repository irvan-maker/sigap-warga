<?php

namespace Database\Seeders;

use App\Models\Rt;
use App\Models\Rw;
use Illuminate\Database\Seeder;

class PilotRegionSeeder extends Seeder
{
    public function run(): void
    {
        $rw = Rw::query()->updateOrCreate(
            ['code' => 'RW-PILOT'],
            ['name' => 'RW Pilot', 'is_active' => true],
        );

        foreach (range(1, 3) as $number) {
            Rt::query()->updateOrCreate(
                [
                    'rw_id' => $rw->id,
                    'code' => sprintf('RT-%02d', $number),
                ],
                [
                    'name' => sprintf('RT Pilot %d', $number),
                    'whatsapp_number' => null,
                    'is_active' => true,
                ],
            );
        }
    }
}
