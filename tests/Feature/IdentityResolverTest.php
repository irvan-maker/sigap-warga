<?php

namespace Tests\Feature;

use App\Models\Citizen;
use App\Models\Rt;
use App\Models\Rw;
use App\Services\IdentityResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class IdentityResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_local_phone_number_resolves_citizen_and_their_territory(): void
    {
        $rt = $this->createRt();
        $citizen = Citizen::factory()->for($rt)->create([
            'phone' => '081234567890',
            'phone_normalized' => '6281234567890',
        ]);

        $context = app(IdentityResolver::class)->resolve(
            '081234567890',
            channel: 'whatsapp',
            message: 'Jalan di depan rumah rusak.',
        );

        $this->assertTrue($context->hasResolvedIdentity());
        $this->assertTrue($context->citizen?->is($citizen));
        $this->assertTrue($context->hasResolvedTerritory());
        $this->assertTrue($context->rt?->is($rt));
        $this->assertSame('whatsapp', $context->channel);
        $this->assertSame('Jalan di depan rumah rusak.', $context->message);
        $this->assertFalse($context->hasResolvedIntent());
    }

    public function test_phone_number_starting_with_eight_resolves_after_normalization(): void
    {
        $citizen = Citizen::factory()->for($this->createRt())->create([
            'phone_normalized' => '6281234567890',
        ]);

        $context = app(IdentityResolver::class)->resolve('81234567890');

        $this->assertTrue($context->citizen?->is($citizen));
    }

    public function test_unknown_phone_leaves_identity_and_territory_unresolved_without_creating_citizen(): void
    {
        Citizen::factory()->for($this->createRt())->create();
        $citizenCount = Citizen::query()->count();

        $context = app(IdentityResolver::class)->resolve('089999999999');

        $this->assertFalse($context->hasResolvedIdentity());
        $this->assertFalse($context->hasResolvedTerritory());
        $this->assertNull($context->citizen);
        $this->assertNull($context->rt);
        $this->assertSame($citizenCount, Citizen::query()->count());
    }

    public function test_resolving_identity_does_not_change_existing_citizen_data(): void
    {
        $citizen = Citizen::factory()->for($this->createRt())->create([
            'name' => 'Warga Existing',
            'phone' => '+62 812-3456-7890',
            'phone_normalized' => '6281234567890',
            'is_active' => false,
        ]);
        $attributesBeforeResolution = DB::table('citizens')->find($citizen->id);

        app(IdentityResolver::class)->resolve('081234567890');

        $this->assertEquals(
            $attributesBeforeResolution,
            DB::table('citizens')->find($citizen->id),
        );
    }

    private function createRt(): Rt
    {
        $rw = Rw::query()->create(['code' => '001', 'name' => 'RW 001']);

        return Rt::query()->create([
            'rw_id' => $rw->id,
            'code' => '001',
            'name' => 'RT 001',
        ]);
    }
}
