<?php

namespace Tests\Feature;

use App\Context\EntryContext;
use App\Context\ServiceContext;
use App\Enums\TerritoryResolutionStatus;
use App\Models\Citizen;
use App\Models\Report;
use App\Models\Rt;
use App\Models\Rw;
use App\Services\ContextResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ContextResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_citizen_territory_is_used_when_entry_has_no_territory(): void
    {
        $rt = $this->createRt();
        $citizen = $this->createCitizen($rt);

        $context = $this->resolve(phone: '081234567890');

        $this->assertTrue($context->citizen?->is($citizen));
        $this->assertTrue($context->rt?->is($rt));
        $this->assertSame(TerritoryResolutionStatus::RESOLVED_FROM_IDENTITY, $context->territoryStatus);
        $this->assertFalse($context->hasTerritoryConflict());
    }

    public function test_entry_territory_is_used_when_citizen_is_not_found(): void
    {
        $rt = $this->createRt();

        $context = $this->resolve(phone: '089999999999', rt: $rt);

        $this->assertFalse($context->hasResolvedIdentity());
        $this->assertTrue($context->rt?->is($rt));
        $this->assertSame(TerritoryResolutionStatus::RESOLVED_FROM_ENTRY, $context->territoryStatus);
    }

    public function test_matching_identity_and_entry_territories_are_verified(): void
    {
        $rt = $this->createRt();
        $this->createCitizen($rt);

        $context = $this->resolve(phone: '81234567890', rt: $rt);

        $this->assertTrue($context->hasResolvedTerritory());
        $this->assertTrue($context->rt?->is($rt));
        $this->assertSame(TerritoryResolutionStatus::VERIFIED, $context->territoryStatus);
        $this->assertFalse($context->hasTerritoryConflict());
    }

    public function test_different_identity_and_entry_territories_produce_conflict_without_mutation(): void
    {
        $rw = $this->createRw();
        $citizenRt = $this->createRt($rw, '001');
        $entryRt = $this->createRt($rw, '002');
        $citizen = $this->createCitizen($citizenRt);
        $citizenBeforeResolution = DB::table('citizens')->find($citizen->id);

        $context = $this->resolve(phone: '081234567890', rt: $entryRt);

        $this->assertTrue($context->hasResolvedIdentity());
        $this->assertFalse($context->hasResolvedTerritory());
        $this->assertNull($context->rt);
        $this->assertTrue($context->hasTerritoryConflict());
        $this->assertSame(TerritoryResolutionStatus::CONFLICT, $context->territoryStatus);
        $this->assertTrue($context->identityRt?->is($citizenRt));
        $this->assertTrue($context->entryRt?->is($entryRt));
        $this->assertEquals($citizenBeforeResolution, DB::table('citizens')->find($citizen->id));
        $this->assertDatabaseCount('reports', 0);
    }

    public function test_inactive_entry_territory_is_retained_but_not_resolved(): void
    {
        $inactiveRt = $this->createRt();
        $inactiveRt->update(['is_active' => false]);

        $context = $this->resolve(phone: null, rt: $inactiveRt);

        $this->assertFalse($context->hasResolvedTerritory());
        $this->assertNull($context->rt);
        $this->assertTrue($context->entryRt?->is($inactiveRt));
        $this->assertTrue($context->hasInvalidEntryTerritory());
        $this->assertSame(TerritoryResolutionStatus::UNRESOLVED, $context->territoryStatus);
    }

    public function test_territory_is_unresolved_without_identity_or_entry_territory(): void
    {
        $context = $this->resolve(phone: null, rt: null);

        $this->assertFalse($context->hasResolvedIdentity());
        $this->assertFalse($context->hasResolvedTerritory());
        $this->assertFalse($context->hasTerritoryConflict());
        $this->assertSame(TerritoryResolutionStatus::UNRESOLVED, $context->territoryStatus);
    }

    public function test_context_keeps_provider_agnostic_entry_channel_and_message(): void
    {
        $context = $this->resolve(
            phone: null,
            channel: 'web',
            message: 'Saya membutuhkan informasi layanan.',
        );

        $this->assertSame('web', $context->channel);
        $this->assertSame('Saya membutuhkan informasi layanan.', $context->message);
        $this->assertDatabaseCount('citizens', 0);
        $this->assertSame(0, Report::query()->count());
    }

    private function resolve(
        ?string $phone,
        ?Rt $rt = null,
        ?string $channel = 'whatsapp',
        ?string $message = 'Pesan warga',
    ): ServiceContext {
        return app(ContextResolver::class)->resolve(new EntryContext(
            channel: $channel,
            message: $message,
            phone: $phone,
            rt: $rt,
        ));
    }

    private function createCitizen(Rt $rt): Citizen
    {
        return Citizen::factory()->for($rt)->create([
            'phone' => '081234567890',
            'phone_normalized' => '6281234567890',
        ]);
    }

    private function createRw(): Rw
    {
        return Rw::query()->create(['code' => '001', 'name' => 'RW 001']);
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
