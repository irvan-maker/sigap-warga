<?php

namespace Tests\Feature;

use App\Models\Citizen;
use App\Models\Rt;
use App\Models\Rw;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CitizenTest extends TestCase
{
    use RefreshDatabase;

    public function test_citizen_belongs_to_rt(): void
    {
        $rt = $this->createRt();
        $citizen = Citizen::factory()->for($rt)->create();

        $this->assertTrue($citizen->rt->is($rt));
    }

    public function test_rt_can_have_multiple_citizens(): void
    {
        $rt = $this->createRt();

        Citizen::factory()->count(3)->for($rt)->create();

        $this->assertCount(3, $rt->citizens);
    }

    public function test_duplicate_normalized_phone_is_rejected_globally(): void
    {
        $rw = $this->createRw();
        $firstRt = $this->createRt($rw, '001');
        $secondRt = $this->createRt($rw, '002');

        Citizen::factory()->for($firstRt)->create([
            'phone' => '0812-3456-7890',
            'phone_normalized' => '6281234567890',
        ]);

        $this->expectException(QueryException::class);

        Citizen::factory()->for($secondRt)->create([
            'phone' => '+62 812 3456 7890',
            'phone_normalized' => '6281234567890',
        ]);
    }

    public function test_citizen_factory_persists_required_data_for_an_rt(): void
    {
        $rt = $this->createRt();

        $citizen = Citizen::factory()->for($rt)->create();

        $this->assertNotEmpty($citizen->name);
        $this->assertNotEmpty($citizen->phone);
        $this->assertSame($citizen->phone, $citizen->phone_normalized);
    }

    private function createRw(string $code = '001'): Rw
    {
        return Rw::query()->create([
            'code' => $code,
            'name' => "RW {$code}",
        ]);
    }

    private function createRt(?Rw $rw = null, string $code = '001'): Rt
    {
        $rw ??= $this->createRw();

        return Rt::query()->create([
            'rw_id' => $rw->id,
            'code' => $code,
            'name' => "RT {$code}",
        ]);
    }
}
