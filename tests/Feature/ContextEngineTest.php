<?php

namespace Tests\Feature;

use App\Context\ContextEngineResult;
use App\Context\EntryContext;
use App\Enums\ContextReadinessStatus;
use App\Enums\NextContextRequirement;
use App\Enums\TerritoryResolutionStatus;
use App\Models\Citizen;
use App\Models\Rt;
use App\Models\Rw;
use App\Services\ContextEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ContextEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_known_active_citizen_resolves_identity_context_and_ready_guidance(): void
    {
        $rt = $this->createRt();
        $citizen = $this->createCitizen($rt);
        $citizenBeforeResolution = DB::table('citizens')->find($citizen->id);

        $result = $this->resolve(phone: '081234567890');

        $this->assertTrue($result->context->citizen?->is($citizen));
        $this->assertTrue($result->context->resolvedContextTerritory()?->is($rt));
        $this->assertSame(TerritoryResolutionStatus::RESOLVED_FROM_IDENTITY, $result->context->territoryStatus);
        $this->assertSame(ContextReadinessStatus::READY, $result->guidance->readinessStatus);
        $this->assertSame(NextContextRequirement::NONE, $result->guidance->nextRequirement);
        $this->assertTrue($result->guidance->canProceed);
        $this->assertEquals($citizenBeforeResolution, DB::table('citizens')->find($citizen->id));
        $this->assertDatabaseCount('reports', 0);
    }

    public function test_unknown_citizen_with_entry_territory_requires_identity(): void
    {
        $entryRt = $this->createRt();

        $result = $this->resolve(phone: '089999999999', rt: $entryRt);

        $this->assertNull($result->context->citizen);
        $this->assertTrue($result->context->resolvedContextTerritory()?->is($entryRt));
        $this->assertSame(ContextReadinessStatus::NEEDS_IDENTITY, $result->guidance->readinessStatus);
        $this->assertSame(NextContextRequirement::IDENTITY, $result->guidance->nextRequirement);
        $this->assertFalse($result->guidance->canProceed);
    }

    public function test_territory_conflict_preserves_candidates_and_requires_confirmation(): void
    {
        $rw = $this->createRw();
        $identityRt = $this->createRt($rw, '003');
        $entryRt = $this->createRt($rw, '010');
        $this->createCitizen($identityRt);

        $result = $this->resolve(phone: '081234567890', rt: $entryRt);

        $this->assertNull($result->context->resolvedContextTerritory());
        $this->assertTrue($result->context->identityRt?->is($identityRt));
        $this->assertTrue($result->context->entryRt?->is($entryRt));
        $this->assertSame(ContextReadinessStatus::TERRITORY_CONFLICT, $result->guidance->readinessStatus);
        $this->assertSame(NextContextRequirement::TERRITORY_CONFIRMATION, $result->guidance->nextRequirement);
    }

    public function test_inactive_citizen_requires_identity_reactivation(): void
    {
        $this->createCitizen($this->createRt(), isActive: false);

        $result = $this->resolve(phone: '081234567890');

        $this->assertSame(ContextReadinessStatus::IDENTITY_INACTIVE, $result->guidance->readinessStatus);
        $this->assertSame(NextContextRequirement::IDENTITY_REACTIVATION, $result->guidance->nextRequirement);
        $this->assertFalse($result->guidance->canProceed);
        $this->assertDatabaseCount('reports', 0);
    }

    public function test_missing_identity_and_territory_requires_both_and_preserves_entry_data(): void
    {
        $result = $this->resolve(
            phone: null,
            channel: 'web',
            message: 'Saya membutuhkan bantuan layanan.',
        );

        $this->assertSame('web', $result->context->channel);
        $this->assertSame('Saya membutuhkan bantuan layanan.', $result->context->message);
        $this->assertSame(ContextReadinessStatus::NEEDS_IDENTITY_AND_TERRITORY, $result->guidance->readinessStatus);
        $this->assertSame(NextContextRequirement::IDENTITY_AND_TERRITORY, $result->guidance->nextRequirement);
        $this->assertFalse($result->guidance->canProceed);
        $this->assertDatabaseCount('citizens', 0);
        $this->assertDatabaseCount('reports', 0);
    }

    private function resolve(
        ?string $phone,
        ?Rt $rt = null,
        ?string $channel = 'whatsapp',
        ?string $message = 'Permintaan layanan warga',
    ): ContextEngineResult {
        return app(ContextEngine::class)->resolve(new EntryContext(
            channel: $channel,
            message: $message,
            phone: $phone,
            rt: $rt,
        ));
    }

    private function createCitizen(Rt $rt, bool $isActive = true): Citizen
    {
        return Citizen::factory()->for($rt)->create([
            'phone' => '081234567890',
            'phone_normalized' => '6281234567890',
            'is_active' => $isActive,
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
