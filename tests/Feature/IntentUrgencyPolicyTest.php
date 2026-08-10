<?php

namespace Tests\Feature;

use App\Context\IntentResolution;
use App\Context\IntentUrgencyValidation;
use App\Context\TerritoryCandidates;
use App\Enums\CitizenIntent;
use App\Enums\IntentUrgencyValidationReason;
use App\Enums\IntentUrgencyValidationStatus;
use App\Enums\TerritoryPurpose;
use App\Enums\UrgencyLevel;
use App\Models\Citizen;
use App\Models\Rt;
use App\Models\Rw;
use App\Services\IntentUrgencyPolicy;
use App\Services\ServiceTerritoryPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class IntentUrgencyPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_emergency_golden_case_preserves_domicile_and_uses_incident_location(): void
    {
        [$identityRt, $incidentRt] = $this->createDifferentTerritories();
        $citizen = Citizen::factory()->for($identityRt)->create();
        $citizenBeforeDecision = DB::table('citizens')->find($citizen->id);
        $identityRtBeforeDecision = DB::table('rts')->find($identityRt->id);
        $incidentRtBeforeDecision = DB::table('rts')->find($incidentRt->id);
        $resolution = new IntentResolution(
            intent: CitizenIntent::EMERGENCY,
            urgency: UrgencyLevel::EMERGENCY,
            incidentRt: $incidentRt,
        );

        $validation = $this->validate($resolution);
        $territoryDecision = app(ServiceTerritoryPolicy::class)->decide(
            $resolution->intent,
            new TerritoryCandidates(
                identityRt: $identityRt,
                entryRt: $incidentRt,
                incidentRt: $resolution->incidentRt,
            ),
        );

        $this->assertTrue($validation->isValid());
        $this->assertTrue($citizen->rt->is($identityRt));
        $this->assertTrue($resolution->incidentRt?->is($incidentRt));
        $this->assertTrue($territoryDecision->preferredRt?->is($incidentRt));
        $this->assertSame(TerritoryPurpose::INCIDENT, $territoryDecision->preferredSource);
        $this->assertEquals($citizenBeforeDecision, DB::table('citizens')->find($citizen->id));
        $this->assertEquals($identityRtBeforeDecision, DB::table('rts')->find($identityRt->id));
        $this->assertEquals($incidentRtBeforeDecision, DB::table('rts')->find($incidentRt->id));
        $this->assertDatabaseCount('reports', 0);
    }

    public function test_quick_report_golden_case_uses_incident_not_identity_territory(): void
    {
        [$identityRt, $incidentRt] = $this->createDifferentTerritories();
        $resolution = new IntentResolution(
            intent: CitizenIntent::REPORT,
            urgency: UrgencyLevel::NORMAL,
            incidentRt: $incidentRt,
        );

        $validation = $this->validate($resolution);
        $territoryDecision = app(ServiceTerritoryPolicy::class)->decide(
            $resolution->intent,
            new TerritoryCandidates(
                identityRt: $identityRt,
                incidentRt: $resolution->incidentRt,
            ),
        );

        $this->assertTrue($validation->isValid());
        $this->assertTrue($territoryDecision->preferredRt?->is($incidentRt));
        $this->assertFalse($territoryDecision->preferredRt?->is($identityRt));
        $this->assertSame(TerritoryPurpose::INCIDENT, $territoryDecision->preferredSource);
    }

    public function test_high_priority_report_is_valid_without_implementing_escalation(): void
    {
        $validation = $this->validate(new IntentResolution(
            intent: CitizenIntent::REPORT,
            urgency: UrgencyLevel::HIGH,
        ));

        $this->assertTrue($validation->isValid());
        $this->assertSame(IntentUrgencyValidationReason::VALID_COMBINATION, $validation->reason);
        $this->assertDatabaseCount('reports', 0);
    }

    #[DataProvider('validCombinations')]
    public function test_valid_combination_matrix(
        CitizenIntent $intent,
        UrgencyLevel $urgency,
    ): void {
        $validation = $this->validate(new IntentResolution($intent, $urgency));

        $this->assertSame(IntentUrgencyValidationStatus::VALID, $validation->status);
        $this->assertTrue($validation->isValid());
    }

    /**
     * @return iterable<string, array{CitizenIntent, UrgencyLevel}>
     */
    public static function validCombinations(): iterable
    {
        yield 'report normal' => [CitizenIntent::REPORT, UrgencyLevel::NORMAL];
        yield 'report high' => [CitizenIntent::REPORT, UrgencyLevel::HIGH];
        yield 'emergency emergency' => [CitizenIntent::EMERGENCY, UrgencyLevel::EMERGENCY];
        yield 'letter normal' => [CitizenIntent::LETTER, UrgencyLevel::NORMAL];
        yield 'information normal' => [CitizenIntent::INFORMATION, UrgencyLevel::NORMAL];
        yield 'aspiration normal' => [CitizenIntent::ASPIRATION, UrgencyLevel::NORMAL];
        yield 'unknown normal' => [CitizenIntent::UNKNOWN, UrgencyLevel::NORMAL];
    }

    #[DataProvider('invalidCombinations')]
    public function test_invalid_combination_matrix(
        CitizenIntent $intent,
        UrgencyLevel $urgency,
        IntentUrgencyValidationReason $reason,
    ): void {
        $validation = $this->validate(new IntentResolution($intent, $urgency));

        $this->assertSame(IntentUrgencyValidationStatus::INVALID, $validation->status);
        $this->assertFalse($validation->isValid());
        $this->assertSame($reason, $validation->reason);
    }

    /**
     * @return iterable<string, array{CitizenIntent, UrgencyLevel, IntentUrgencyValidationReason}>
     */
    public static function invalidCombinations(): iterable
    {
        yield 'report emergency' => [CitizenIntent::REPORT, UrgencyLevel::EMERGENCY, IntentUrgencyValidationReason::EMERGENCY_URGENCY_REQUIRES_EMERGENCY_INTENT];
        yield 'emergency normal' => [CitizenIntent::EMERGENCY, UrgencyLevel::NORMAL, IntentUrgencyValidationReason::EMERGENCY_INTENT_REQUIRES_EMERGENCY_URGENCY];
        yield 'emergency high' => [CitizenIntent::EMERGENCY, UrgencyLevel::HIGH, IntentUrgencyValidationReason::EMERGENCY_INTENT_REQUIRES_EMERGENCY_URGENCY];
        yield 'letter high' => [CitizenIntent::LETTER, UrgencyLevel::HIGH, IntentUrgencyValidationReason::NORMAL_URGENCY_REQUIRED];
        yield 'letter emergency' => [CitizenIntent::LETTER, UrgencyLevel::EMERGENCY, IntentUrgencyValidationReason::EMERGENCY_URGENCY_REQUIRES_EMERGENCY_INTENT];
        yield 'information high' => [CitizenIntent::INFORMATION, UrgencyLevel::HIGH, IntentUrgencyValidationReason::NORMAL_URGENCY_REQUIRED];
        yield 'information emergency' => [CitizenIntent::INFORMATION, UrgencyLevel::EMERGENCY, IntentUrgencyValidationReason::EMERGENCY_URGENCY_REQUIRES_EMERGENCY_INTENT];
        yield 'aspiration high' => [CitizenIntent::ASPIRATION, UrgencyLevel::HIGH, IntentUrgencyValidationReason::NORMAL_URGENCY_REQUIRED];
        yield 'aspiration emergency' => [CitizenIntent::ASPIRATION, UrgencyLevel::EMERGENCY, IntentUrgencyValidationReason::EMERGENCY_URGENCY_REQUIRES_EMERGENCY_INTENT];
        yield 'unknown high' => [CitizenIntent::UNKNOWN, UrgencyLevel::HIGH, IntentUrgencyValidationReason::NORMAL_URGENCY_REQUIRED];
        yield 'unknown emergency' => [CitizenIntent::UNKNOWN, UrgencyLevel::EMERGENCY, IntentUrgencyValidationReason::EMERGENCY_URGENCY_REQUIRES_EMERGENCY_INTENT];
    }

    private function validate(IntentResolution $resolution): IntentUrgencyValidation
    {
        return app(IntentUrgencyPolicy::class)->validate($resolution);
    }

    /**
     * @return array{Rt, Rt}
     */
    private function createDifferentTerritories(): array
    {
        $identityRw = Rw::query()->create(['code' => '001', 'name' => 'RW 01']);
        $incidentRw = Rw::query()->create(['code' => '005', 'name' => 'RW 05']);

        return [
            $this->createRt($identityRw, '001'),
            $this->createRt($incidentRw, '010'),
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
