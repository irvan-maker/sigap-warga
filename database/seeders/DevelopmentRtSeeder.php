<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Rt;
use App\Models\Rw;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class DevelopmentRtSeeder extends Seeder
{
    public function run(): void
    {
        $rw = Rw::query()->where('code', 'RW-PILOT')->first();

        if ($rw === null) {
            throw new RuntimeException('Development RT seeding failed: pilot region RW-PILOT was not found.');
        }

        $rt = Rt::query()
            ->where('rw_id', $rw->id)
            ->where('code', 'RT-01')
            ->first();

        if ($rt === null) {
            throw new RuntimeException('Development RT seeding failed: RT-01 was not found in RW-PILOT.');
        }

        User::query()->updateOrCreate(
            ['email' => 'rt01@sigapwarga.test'],
            [
                'name' => 'Development RT 01',
                'password' => Hash::make('password'),
                'role' => UserRole::RT,
                'is_active' => true,
                'rw_id' => $rw->id,
                'rt_id' => $rt->id,
            ],
        );
    }
}
