<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Rt;
use App\Models\Rw;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterRegionTest extends TestCase
{
    use RefreshDatabase;

    public function test_rw_can_have_multiple_rts(): void
    {
        $rw = $this->createRw();

        $this->createRt($rw, '001');
        $this->createRt($rw, '002');
        $this->createRt($rw, '003');

        $this->assertCount(3, $rw->rts);
    }

    public function test_rt_belongs_to_rw(): void
    {
        $rw = $this->createRw();
        $rt = $this->createRt($rw);

        $this->assertTrue($rt->rw->is($rw));
    }

    public function test_duplicate_rw_code_is_rejected_by_database(): void
    {
        $this->createRw('001');

        $this->expectException(QueryException::class);

        $this->createRw('001');
    }

    public function test_duplicate_rt_code_within_same_rw_is_rejected(): void
    {
        $rw = $this->createRw();
        $this->createRt($rw, '001');

        $this->expectException(QueryException::class);

        $this->createRt($rw, '001');
    }

    public function test_same_rt_code_is_allowed_in_different_rws(): void
    {
        $firstRw = $this->createRw('001');
        $secondRw = $this->createRw('002');

        $this->createRt($firstRw, '001');
        $this->createRt($secondRw, '001');

        $this->assertDatabaseCount('rts', 2);
    }

    public function test_user_can_belong_to_rw(): void
    {
        $rw = $this->createRw();
        $user = User::factory()->create([
            'role' => UserRole::RW,
            'rw_id' => $rw->id,
            'rt_id' => null,
        ]);

        $this->assertTrue($user->rw->is($rw));
        $this->assertNull($user->rt);
    }

    public function test_user_can_belong_to_rt(): void
    {
        $rw = $this->createRw();
        $rt = $this->createRt($rw);
        $user = User::factory()->create([
            'role' => UserRole::RT,
            'rw_id' => $rw->id,
            'rt_id' => $rt->id,
        ]);

        $this->assertTrue($user->rw->is($rw));
        $this->assertTrue($user->rt->is($rt));
    }

    private function createRw(string $code = '001'): Rw
    {
        return Rw::query()->create([
            'code' => $code,
            'name' => "RW {$code}",
        ]);
    }

    private function createRt(Rw $rw, string $code = '001'): Rt
    {
        return $rw->rts()->create([
            'code' => $code,
            'name' => "RT {$code}",
        ]);
    }
}
