<?php

namespace Database\Seeders;

use App\Models\Citizen;
use App\Models\Rt;
use Illuminate\Database\Seeder;

class PilotCitizenSeeder extends Seeder
{
    public function run(): void
    {
        Rt::query()
            ->whereIn('code', ['RT-01', 'RT-02', 'RT-03'])
            ->orderBy('code')
            ->get()
            ->each(function (Rt $rt, int $index): void {
                $number = $index + 1;
                $phone = sprintf('62800000000%02d', $number);

                Citizen::query()->updateOrCreate(
                    [
                        'rt_id' => $rt->id,
                        'phone_normalized' => $phone,
                    ],
                    [
                        'name' => sprintf('Warga Pilot %d', $number),
                        'phone' => $phone,
                    ],
                );
            });
    }
}
