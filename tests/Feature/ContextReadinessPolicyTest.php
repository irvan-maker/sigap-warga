<?php

namespace Tests\Feature;

use App\Context\EntryContext;
use App\Context\ServiceContext;
use App\Enums\ContextReadinessStatus;
use App\Enums\TerritoryResolutionStatus;
use App\Models\Citizen;
use App\Models\Rt;
use App\Models\Rw;
use App\Services\ContextReadinessPolicy;
use App\Services\ContextResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ContextReadinessPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_citizen_with_valid_territory_is_ready(): void
    {
        $rt = $this->createRt();
        $this->createCitizen($rt);

        $this->assertSame(
            ContextReadinessStatus::READY,
            $this->evaluate($this->resolve(phone: '081234567890')),
        );
    }

    public function test_active_citizen_with_unresolved_territory_needs_territory(): void
    {
        $inactiveRt = $this->createRt();
        $inactiveRt->update(['is_active' => false]);
        $this->createCitizen($inactiveRt);

        $this->assertSame(
            ContextReadinessStatus::NEEDS_TERRITORY,
            $this->evaluate($this->resolve(phone: '081234567890')),
        );
    }

    public function test_active_citizen_with_territory_conflict_reports_conflict(): void
    {
        $rw = $this->createRw();
        $identityRt = $this->createRt($rw, '003');
        $entryRt = $this->createRt($rw, '007');
        $this->createCitizen($identityRt);

        $context = $this->resolve(phone: '081234567890', rt: $entryRt);

        $this->assertSame(ContextReadinessStatus::TERRITORY_CONFLICT, $this->evaluate($context));
        $this->assertTrue($context->identityRt?->is($identityRt));
        $this->assertTrue($context->entryRt?->is($entryRt));
    }

    public function test_inactive_citizen_is_not_ready_for_normal_service(): void
    {
        $citizen = $this->createCitizen($this->createRt(), isActive: false);
        $citizenBeforeEvaluation = DB::table('citizens')->find($citizen->id);

        $status = $this->evaluate($this->resolve(phone: '081234567890'));

        $this->assertSame(ContextReadinessStatus::IDENTITY_INACTIVE, $status);
        $this->assertEquals($citizenBeforeEvaluation, DB::table('citizens')->find($citizen->id));
        $this->assertDatabaseCount('reports', 0);
    }

    public function test_unknown_citizen_with_valid_entry_territory_needs_identity(): void
    {
        $this->assertSame(
            ContextReadinessStatus::NEEDS_IDENTITY,
            $this->evaluate($this->resolve(phone: '089999999999', rt: $this->createRt())),
        );
    }

    public function test_unknown_citizen_without_territory_needs_both(): void
    {
        $this->assertSame(
            ContextReadinessStatus::NEEDS_IDENTITY_AND_TERRITORY,
            $this->evaluate($this->resolve(phone: null)),
        );
    }

    public function test_inactive_entry_territory_does_not_satisfy_readiness(): void
    {
        $inactiveRt = $this->createRt();
        $inactiveRt->update(['is_active' => false]);
        $context = $this->resolve(phone: null, rt: $inactiveRt);

        $this->assertSame(ContextReadinessStatus::NEEDS_IDENTITY_AND_TERRITORY, $this->evaluate($context));
        $this->assertSame(TerritoryResolutionStatus::UNRESOLVED, $context->territoryStatus);
        $this->assertTrue($context->hasInvalidEntryTerritory());
    }

    private function evaluate(ServiceContext $context): ContextReadinessStatus
    {
        return app(ContextReadinessPolicy::class)->evaluate($context);
    }

    private function resolve(?string $phone, ?Rt $rt = null): ServiceContext
    {
        return app(ContextResolver::class)->resolve(new EntryContext(
            channel: 'web',
            message: 'Permintaan layanan warga',
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
