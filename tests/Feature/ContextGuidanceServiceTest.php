<?php

namespace Tests\Feature;

use App\Context\EntryContext;
use App\Enums\ContextGuidanceReason;
use App\Enums\ContextReadinessStatus;
use App\Enums\NextContextRequirement;
use App\Models\Citizen;
use App\Models\Rt;
use App\Models\Rw;
use App\Services\ContextGuidanceService;
use App\Services\ContextReadinessPolicy;
use App\Services\ContextResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ContextGuidanceServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_ready_context_can_proceed_without_another_requirement(): void
    {
        $this->assertGuidance(
            ContextReadinessStatus::READY,
            NextContextRequirement::NONE,
            true,
            ContextGuidanceReason::CONTEXT_READY,
        );
    }

    public function test_context_needing_identity_requires_identity(): void
    {
        $this->assertGuidance(
            ContextReadinessStatus::NEEDS_IDENTITY,
            NextContextRequirement::IDENTITY,
            false,
            ContextGuidanceReason::IDENTITY_REQUIRED,
        );
    }

    public function test_context_needing_territory_requires_territory(): void
    {
        $this->assertGuidance(
            ContextReadinessStatus::NEEDS_TERRITORY,
            NextContextRequirement::TERRITORY,
            false,
            ContextGuidanceReason::TERRITORY_REQUIRED,
        );
    }

    public function test_context_needing_identity_and_territory_requires_both(): void
    {
        $this->assertGuidance(
            ContextReadinessStatus::NEEDS_IDENTITY_AND_TERRITORY,
            NextContextRequirement::IDENTITY_AND_TERRITORY,
            false,
            ContextGuidanceReason::IDENTITY_AND_TERRITORY_REQUIRED,
        );
    }

    public function test_territory_conflict_requires_confirmation(): void
    {
        $this->assertGuidance(
            ContextReadinessStatus::TERRITORY_CONFLICT,
            NextContextRequirement::TERRITORY_CONFIRMATION,
            false,
            ContextGuidanceReason::TERRITORY_CONFIRMATION_REQUIRED,
        );
    }

    public function test_inactive_identity_requires_reactivation(): void
    {
        $this->assertGuidance(
            ContextReadinessStatus::IDENTITY_INACTIVE,
            NextContextRequirement::IDENTITY_REACTIVATION,
            false,
            ContextGuidanceReason::IDENTITY_REACTIVATION_REQUIRED,
        );
    }

    public function test_conflict_decision_keeps_candidate_territories_without_side_effects(): void
    {
        $rw = Rw::query()->create(['code' => '001', 'name' => 'RW 001']);
        $identityRt = $this->createRt($rw, '003');
        $entryRt = $this->createRt($rw, '007');
        $citizen = Citizen::factory()->for($identityRt)->create([
            'phone' => '081234567890',
            'phone_normalized' => '6281234567890',
        ]);
        $citizenBeforeDecision = DB::table('citizens')->find($citizen->id);
        $context = app(ContextResolver::class)->resolve(new EntryContext(
            channel: 'web',
            message: 'Permintaan layanan warga',
            phone: '081234567890',
            rt: $entryRt,
        ));
        $readiness = app(ContextReadinessPolicy::class)->evaluate($context);

        $decision = app(ContextGuidanceService::class)->decide($readiness);

        $this->assertSame(NextContextRequirement::TERRITORY_CONFIRMATION, $decision->nextRequirement);
        $this->assertTrue($context->identityRt?->is($identityRt));
        $this->assertTrue($context->entryRt?->is($entryRt));
        $this->assertEquals($citizenBeforeDecision, DB::table('citizens')->find($citizen->id));
        $this->assertDatabaseCount('reports', 0);
    }

    private function assertGuidance(
        ContextReadinessStatus $readiness,
        NextContextRequirement $requirement,
        bool $canProceed,
        ContextGuidanceReason $reason,
    ): void {
        $decision = app(ContextGuidanceService::class)->decide($readiness);

        $this->assertSame($readiness, $decision->readinessStatus);
        $this->assertSame($requirement, $decision->nextRequirement);
        $this->assertSame($canProceed, $decision->canProceed);
        $this->assertSame($reason, $decision->reasonCode);
    }

    private function createRt(Rw $rw, string $code): Rt
    {
        return Rt::query()->create([
            'rw_id' => $rw->id,
            'code' => $code,
            'name' => "RT {$code}",
        ]);
    }
}
