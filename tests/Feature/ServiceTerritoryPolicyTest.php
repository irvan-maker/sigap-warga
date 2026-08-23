<?php

namespace Tests\Feature;

use App\Context\ServiceTerritoryDecision;
use App\Context\TerritoryCandidates;
use App\Enums\CitizenIntent;
use App\Enums\ServiceTerritoryStatus;
use App\Enums\TerritoryPurpose;
use App\Enums\UrgencyLevel;
use App\Models\Citizen;
use App\Models\Rt;
use App\Models\Rw;
use App\Services\ServiceTerritoryPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ServiceTerritoryPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_letter_prefers_identity_territory_over_different_entry_territory(): void
    {
        [$identityRt, $otherRt] = $this->createDifferentTerritories();

        $decision = $this->decide(CitizenIntent::LETTER, new TerritoryCandidates(
            identityRt: $identityRt,
            entryRt: $otherRt,
        ));

        $this->assertResolvedFrom($decision, $identityRt, TerritoryPurpose::IDENTITY);
    }

    public function test_report_prefers_incident_territory_over_identity_territory(): void
    {
        [$identityRt, $incidentRt] = $this->createDifferentTerritories();

        $decision = $this->decide(CitizenIntent::REPORT, new TerritoryCandidates(
            identityRt: $identityRt,
            incidentRt: $incidentRt,
        ));

        $this->assertResolvedFrom($decision, $incidentRt, TerritoryPurpose::INCIDENT);
    }

    public function test_emergency_location_is_not_overridden_by_identity_territory(): void
    {
        [$identityRt, $incidentRt] = $this->createDifferentTerritories();
        $citizen = Citizen::factory()->for($identityRt)->create();
        $citizenBeforeDecision = DB::table('citizens')->find($citizen->id);
        $territories = new TerritoryCandidates(
            identityRt: $identityRt,
            incidentRt: $incidentRt,
        );

        $decision = $this->decide(CitizenIntent::EMERGENCY, $territories);

        $this->assertResolvedFrom($decision, $incidentRt, TerritoryPurpose::INCIDENT);
        $this->assertTrue($territories->identityRt?->is($identityRt));
        $this->assertTrue($territories->incidentRt?->is($incidentRt));
        $this->assertEquals($citizenBeforeDecision, DB::table('citizens')->find($citizen->id));
        $this->assertDatabaseCount('reports', 0);
    }

    public function test_general_information_allows_territory_to_be_optional(): void
    {
        $decision = $this->decide(CitizenIntent::INFORMATION, new TerritoryCandidates);

        $this->assertSame(ServiceTerritoryStatus::OPTIONAL, $decision->status);
        $this->assertNull($decision->preferredRt);
        $this->assertNull($decision->preferredSource);
    }

    public function test_unknown_intent_does_not_select_territory_aggressively(): void
    {
        [$identityRt, $entryRt] = $this->createDifferentTerritories();

        $decision = $this->decide(CitizenIntent::UNKNOWN, new TerritoryCandidates(
            identityRt: $identityRt,
            entryRt: $entryRt,
        ));

        $this->assertSame(ServiceTerritoryStatus::UNRESOLVED, $decision->status);
        $this->assertFalse($decision->isResolved());
        $this->assertNull($decision->preferredRt);
        $this->assertNull($decision->preferredSource);
    }

    public function test_report_uses_domicile_for_conflict_entry_for_anonymous_and_not_identity_alone(): void
    {
        [$identityRt, $entryRt] = $this->createDifferentTerritories();

        $entryDecision = $this->decide(CitizenIntent::REPORT, new TerritoryCandidates(
            identityRt: $identityRt,
            entryRt: $entryRt,
        ));
        $identityOnlyDecision = $this->decide(CitizenIntent::REPORT, new TerritoryCandidates(
            identityRt: $identityRt,
        ));
        $entryOnlyDecision = $this->decide(CitizenIntent::REPORT, new TerritoryCandidates(
            entryRt: $entryRt,
        ));

        $this->assertResolvedFrom($entryDecision, $identityRt, TerritoryPurpose::IDENTITY);
        $this->assertResolvedFrom($entryOnlyDecision, $entryRt, TerritoryPurpose::ENTRY);
        $this->assertSame(ServiceTerritoryStatus::UNRESOLVED, $identityOnlyDecision->status);
        $this->assertNull($identityOnlyDecision->preferredRt);
    }

    public function test_quick_high_priority_and_emergency_remain_distinct_domain_contracts(): void
    {
        $this->assertNotSame(CitizenIntent::REPORT, CitizenIntent::EMERGENCY);
        $this->assertSame('normal', UrgencyLevel::NORMAL->value);
        $this->assertSame('high', UrgencyLevel::HIGH->value);
        $this->assertSame('emergency', UrgencyLevel::EMERGENCY->value);
    }

    private function decide(
        CitizenIntent $intent,
        TerritoryCandidates $territories,
    ): ServiceTerritoryDecision {
        return app(ServiceTerritoryPolicy::class)->decide($intent, $territories);
    }

    private function assertResolvedFrom(
        ServiceTerritoryDecision $decision,
        Rt $expectedRt,
        TerritoryPurpose $expectedPurpose,
    ): void {
        $this->assertTrue($decision->isResolved());
        $this->assertSame(ServiceTerritoryStatus::RESOLVED, $decision->status);
        $this->assertTrue($decision->preferredRt?->is($expectedRt));
        $this->assertSame($expectedPurpose, $decision->preferredSource);
    }

    /**
     * @return array{Rt, Rt}
     */
    private function createDifferentTerritories(): array
    {
        $identityRw = Rw::query()->create(['code' => '001', 'name' => 'RW 01']);
        $otherRw = Rw::query()->create(['code' => '005', 'name' => 'RW 05']);

        return [
            $this->createRt($identityRw, '001'),
            $this->createRt($otherRw, '010'),
        ];
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
