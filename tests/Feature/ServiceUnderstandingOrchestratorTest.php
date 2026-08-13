<?php

namespace Tests\Feature;

use App\Context\CitizenServiceUnderstanding;
use App\Context\EntryContext;
use App\Context\IntentResolution;
use App\Enums\CitizenIntent;
use App\Enums\ContextReadinessStatus;
use App\Enums\ServiceTerritoryStatus;
use App\Enums\TerritoryPurpose;
use App\Enums\UrgencyLevel;
use App\Models\Citizen;
use App\Models\Rt;
use App\Models\Rw;
use App\Services\ServiceUnderstandingOrchestrator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ServiceUnderstandingOrchestratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_quick_report_can_proceed_with_incident_service_territory(): void
    {
        [$identityRt, $incidentRt] = $this->createDifferentTerritories();
        $this->createCitizen($identityRt);

        $understanding = $this->understand(
            phone: '081234567890',
            intent: CitizenIntent::REPORT,
            urgency: UrgencyLevel::NORMAL,
            incidentRt: $incidentRt,
        );

        $this->assertTrue($understanding->isContextReady());
        $this->assertTrue($understanding->isIntentUrgencyValid());
        $this->assertTrue($understanding->serviceTerritoryDecision->preferredRt?->is($incidentRt));
        $this->assertSame(TerritoryPurpose::INCIDENT, $understanding->serviceTerritoryDecision->preferredSource);
        $this->assertTrue($understanding->canProceedToRouting());
    }

    public function test_high_priority_report_can_proceed_without_escalation_side_effects(): void
    {
        [$identityRt, $incidentRt] = $this->createDifferentTerritories();
        $this->createCitizen($identityRt);

        $understanding = $this->understand(
            phone: '081234567890',
            intent: CitizenIntent::REPORT,
            urgency: UrgencyLevel::HIGH,
            incidentRt: $incidentRt,
        );

        $this->assertTrue($understanding->canProceedToRouting());
        $this->assertDatabaseCount('reports', 0);
    }

    public function test_emergency_incident_clarifies_entry_conflict_without_changing_domicile(): void
    {
        [$identityRt, $incidentRt] = $this->createDifferentTerritories();
        $citizen = $this->createCitizen($identityRt);
        $citizenBeforeUnderstanding = DB::table('citizens')->find($citizen->id);
        $identityRtBeforeUnderstanding = DB::table('rts')->find($identityRt->id);
        $incidentRtBeforeUnderstanding = DB::table('rts')->find($incidentRt->id);

        $understanding = $this->understand(
            phone: '081234567890',
            entryRt: $incidentRt,
            intent: CitizenIntent::EMERGENCY,
            urgency: UrgencyLevel::EMERGENCY,
            incidentRt: $incidentRt,
        );

        $this->assertFalse($understanding->isContextReady());
        $this->assertTrue($understanding->contextResult->context->identityRt?->is($identityRt));
        $this->assertTrue($understanding->contextResult->context->entryRt?->is($incidentRt));
        $this->assertTrue($understanding->isTerritoryConflictClarifiedByIncident());
        $this->assertTrue($understanding->isIntentUrgencyValid());
        $this->assertTrue($understanding->serviceTerritoryDecision->preferredRt?->is($incidentRt));
        $this->assertTrue($understanding->canProceedToRouting());
        $this->assertEquals($citizenBeforeUnderstanding, DB::table('citizens')->find($citizen->id));
        $this->assertEquals($identityRtBeforeUnderstanding, DB::table('rts')->find($identityRt->id));
        $this->assertEquals($incidentRtBeforeUnderstanding, DB::table('rts')->find($incidentRt->id));
        $this->assertDatabaseCount('reports', 0);
    }

    public function test_report_pre_intent_conflict_can_proceed_at_domicile_intake(): void
    {
        [$identityRt, $entryRt] = $this->createDifferentTerritories();
        $this->createCitizen($identityRt);

        $understanding = $this->understand(
            phone: '081234567890',
            entryRt: $entryRt,
            intent: CitizenIntent::REPORT,
            urgency: UrgencyLevel::NORMAL,
        );

        $this->assertSame(ContextReadinessStatus::TERRITORY_CONFLICT, $understanding->contextResult->guidance->readinessStatus);
        $this->assertFalse($understanding->isTerritoryConflictClarifiedByIncident());
        $this->assertTrue($understanding->isTerritoryConflictAcceptedAtDomicile());
        $this->assertTrue($understanding->serviceTerritoryDecision->preferredRt?->is($identityRt));
        $this->assertTrue($understanding->canProceedToRouting());
    }

    public function test_invalid_intent_urgency_combination_cannot_proceed(): void
    {
        [$identityRt, $incidentRt] = $this->createDifferentTerritories();
        $this->createCitizen($identityRt);

        $understanding = $this->understand(
            phone: '081234567890',
            intent: CitizenIntent::REPORT,
            urgency: UrgencyLevel::EMERGENCY,
            incidentRt: $incidentRt,
        );

        $this->assertFalse($understanding->isIntentUrgencyValid());
        $this->assertFalse($understanding->canProceedToRouting());
    }

    public function test_unknown_intent_cannot_proceed(): void
    {
        $identityRt = $this->createRt($this->createRw('001'), '001');
        $this->createCitizen($identityRt);

        $understanding = $this->understand(
            phone: '081234567890',
            intent: CitizenIntent::UNKNOWN,
            urgency: UrgencyLevel::NORMAL,
        );

        $this->assertTrue($understanding->isIntentUrgencyValid());
        $this->assertSame(ServiceTerritoryStatus::UNRESOLVED, $understanding->serviceTerritoryDecision->status);
        $this->assertFalse($understanding->canProceedToRouting());
    }

    public function test_general_information_can_proceed_with_optional_service_territory(): void
    {
        $identityRt = $this->createRt($this->createRw('001'), '001');
        $this->createCitizen($identityRt);

        $understanding = $this->understand(
            phone: '081234567890',
            intent: CitizenIntent::INFORMATION,
            urgency: UrgencyLevel::NORMAL,
        );

        $this->assertTrue($understanding->isContextReady());
        $this->assertSame(ServiceTerritoryStatus::OPTIONAL, $understanding->serviceTerritoryDecision->status);
        $this->assertFalse($understanding->hasResolvedServiceTerritory());
        $this->assertTrue($understanding->canProceedToRouting());
        $this->assertDatabaseCount('reports', 0);
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

    private function createCitizen(Rt $rt): Citizen
    {
        return Citizen::factory()->for($rt)->create([
            'phone' => '081234567890',
            'phone_normalized' => '6281234567890',
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
