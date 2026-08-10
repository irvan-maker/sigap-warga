<?php

namespace Tests\Feature;

use App\Context\CitizenServiceUnderstanding;
use App\Context\EntryContext;
use App\Context\IntentResolution;
use App\Context\RoutingReadiness;
use App\Enums\CitizenIntent;
use App\Enums\RoutingReadinessReason;
use App\Enums\RoutingReadinessStatus;
use App\Enums\UrgencyLevel;
use App\Models\Citizen;
use App\Models\Rt;
use App\Models\Rw;
use App\Services\RoutingReadinessDiagnoser;
use App\Services\ServiceUnderstandingOrchestrator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RoutingReadinessDiagnoserTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_understanding_is_ready_for_routing(): void
    {
        [$identityRt, $incidentRt] = $this->createDifferentTerritories();
        $this->createCitizen($identityRt);

        $readiness = $this->diagnose($this->understand(
            phone: '081234567890',
            intent: CitizenIntent::REPORT,
            urgency: UrgencyLevel::NORMAL,
            incidentRt: $incidentRt,
        ));

        $this->assertSame(RoutingReadinessStatus::READY, $readiness->status);
        $this->assertSame(RoutingReadinessReason::READY, $readiness->reason);
        $this->assertTrue($readiness->canProceed());
    }

    public function test_missing_identity_has_identity_required_reason(): void
    {
        $entryRt = $this->createRt($this->createRw('001'), '001');

        $readiness = $this->diagnose($this->understand(
            phone: '089999999999',
            entryRt: $entryRt,
            intent: CitizenIntent::INFORMATION,
            urgency: UrgencyLevel::NORMAL,
        ));

        $this->assertBlockedFor($readiness, RoutingReadinessReason::IDENTITY_REQUIRED);
    }

    public function test_missing_territory_has_territory_required_reason(): void
    {
        $inactiveRt = $this->createRt($this->createRw('001'), '001');
        $inactiveRt->update(['is_active' => false]);
        $this->createCitizen($inactiveRt);

        $readiness = $this->diagnose($this->understand(
            phone: '081234567890',
            intent: CitizenIntent::LETTER,
            urgency: UrgencyLevel::NORMAL,
        ));

        $this->assertBlockedFor($readiness, RoutingReadinessReason::TERRITORY_REQUIRED);
    }

    public function test_missing_identity_and_territory_has_combined_reason(): void
    {
        $readiness = $this->diagnose($this->understand(
            phone: null,
            intent: CitizenIntent::INFORMATION,
            urgency: UrgencyLevel::NORMAL,
        ));

        $this->assertBlockedFor($readiness, RoutingReadinessReason::IDENTITY_AND_TERRITORY_REQUIRED);
    }

    public function test_inactive_citizen_has_identity_inactive_reason(): void
    {
        $rt = $this->createRt($this->createRw('001'), '001');
        $this->createCitizen($rt, isActive: false);

        $readiness = $this->diagnose($this->understand(
            phone: '081234567890',
            intent: CitizenIntent::LETTER,
            urgency: UrgencyLevel::NORMAL,
        ));

        $this->assertBlockedFor($readiness, RoutingReadinessReason::IDENTITY_INACTIVE);
    }

    public function test_unclarified_conflict_requires_territory_confirmation(): void
    {
        [$identityRt, $entryRt] = $this->createDifferentTerritories();
        $this->createCitizen($identityRt);

        $readiness = $this->diagnose($this->understand(
            phone: '081234567890',
            entryRt: $entryRt,
            intent: CitizenIntent::REPORT,
            urgency: UrgencyLevel::NORMAL,
        ));

        $this->assertBlockedFor($readiness, RoutingReadinessReason::TERRITORY_CONFIRMATION_REQUIRED);
    }

    public function test_incident_clarified_conflict_is_ready_without_side_effects(): void
    {
        [$identityRt, $incidentRt] = $this->createDifferentTerritories();
        $citizen = $this->createCitizen($identityRt);
        $citizenBeforeDiagnosis = DB::table('citizens')->find($citizen->id);
        $identityRtBeforeDiagnosis = DB::table('rts')->find($identityRt->id);
        $incidentRtBeforeDiagnosis = DB::table('rts')->find($incidentRt->id);

        $readiness = $this->diagnose($this->understand(
            phone: '081234567890',
            entryRt: $incidentRt,
            intent: CitizenIntent::EMERGENCY,
            urgency: UrgencyLevel::EMERGENCY,
            incidentRt: $incidentRt,
        ));

        $this->assertSame(RoutingReadinessReason::READY, $readiness->reason);
        $this->assertTrue($readiness->canProceed());
        $this->assertEquals($citizenBeforeDiagnosis, DB::table('citizens')->find($citizen->id));
        $this->assertEquals($identityRtBeforeDiagnosis, DB::table('rts')->find($identityRt->id));
        $this->assertEquals($incidentRtBeforeDiagnosis, DB::table('rts')->find($incidentRt->id));
        $this->assertDatabaseCount('reports', 0);
    }

    public function test_unknown_intent_has_intent_unknown_reason(): void
    {
        $rt = $this->createRt($this->createRw('001'), '001');
        $this->createCitizen($rt);

        $readiness = $this->diagnose($this->understand(
            phone: '081234567890',
            intent: CitizenIntent::UNKNOWN,
            urgency: UrgencyLevel::NORMAL,
        ));

        $this->assertBlockedFor($readiness, RoutingReadinessReason::INTENT_UNKNOWN);
    }

    public function test_invalid_intent_urgency_has_validation_reason(): void
    {
        [$identityRt, $incidentRt] = $this->createDifferentTerritories();
        $this->createCitizen($identityRt);

        $readiness = $this->diagnose($this->understand(
            phone: '081234567890',
            intent: CitizenIntent::REPORT,
            urgency: UrgencyLevel::EMERGENCY,
            incidentRt: $incidentRt,
        ));

        $this->assertBlockedFor($readiness, RoutingReadinessReason::INTENT_URGENCY_INVALID);
    }

    public function test_required_service_territory_unresolved_has_specific_reason(): void
    {
        $identityRt = $this->createRt($this->createRw('001'), '001');
        $this->createCitizen($identityRt);

        $readiness = $this->diagnose($this->understand(
            phone: '081234567890',
            intent: CitizenIntent::REPORT,
            urgency: UrgencyLevel::NORMAL,
        ));

        $this->assertBlockedFor($readiness, RoutingReadinessReason::SERVICE_TERRITORY_UNRESOLVED);
    }

    public function test_general_information_with_optional_territory_is_ready(): void
    {
        $identityRt = $this->createRt($this->createRw('001'), '001');
        $this->createCitizen($identityRt);

        $readiness = $this->diagnose($this->understand(
            phone: '081234567890',
            intent: CitizenIntent::INFORMATION,
            urgency: UrgencyLevel::NORMAL,
        ));

        $this->assertSame(RoutingReadinessReason::READY, $readiness->reason);
        $this->assertTrue($readiness->canProceed());
        $this->assertDatabaseCount('reports', 0);
    }

    private function diagnose(CitizenServiceUnderstanding $understanding): RoutingReadiness
    {
        return app(RoutingReadinessDiagnoser::class)->diagnose($understanding);
    }

    private function understand(
        ?string $phone,
        CitizenIntent $intent,
        UrgencyLevel $urgency,
        ?Rt $entryRt = null,
        ?Rt $incidentRt = null,
    ): CitizenServiceUnderstanding {
        return app(ServiceUnderstandingOrchestrator::class)->understand(
            new EntryContext(
                channel: 'web',
                message: 'Permintaan layanan warga',
                phone: $phone,
                rt: $entryRt,
            ),
            new IntentResolution(
                intent: $intent,
                urgency: $urgency,
                incidentRt: $incidentRt,
            ),
        );
    }

    private function assertBlockedFor(
        RoutingReadiness $readiness,
        RoutingReadinessReason $reason,
    ): void {
        $this->assertSame(RoutingReadinessStatus::BLOCKED, $readiness->status);
        $this->assertSame($reason, $readiness->reason);
        $this->assertFalse($readiness->canProceed());
    }

    private function createCitizen(Rt $rt, bool $isActive = true): Citizen
    {
        return Citizen::factory()->for($rt)->create([
            'phone' => '081234567890',
            'phone_normalized' => '6281234567890',
            'is_active' => $isActive,
        ]);
    }

    /**
     * @return array{Rt, Rt}
     */
    private function createDifferentTerritories(): array
    {
        return [
            $this->createRt($this->createRw('001'), '001'),
            $this->createRt($this->createRw('005'), '010'),
        ];
    }

    private function createRw(string $code): Rw
    {
        return Rw::query()->create(['code' => $code, 'name' => "RW {$code}"]);
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
