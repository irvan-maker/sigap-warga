<?php

namespace Tests\Feature;

use App\Context\CitizenRequestUnderstanding;
use App\Context\EntryContext;
use App\Enums\CitizenIntent;
use App\Enums\IntentRule;
use App\Enums\RoutingReadinessReason;
use App\Enums\RoutingReadinessStatus;
use App\Enums\ServiceTerritoryStatus;
use App\Enums\TerritoryPurpose;
use App\Enums\UrgencyLevel;
use App\Models\Citizen;
use App\Models\Rt;
use App\Models\Rw;
use App\Services\CitizenRequestInterpreter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CitizenRequestInterpreterTest extends TestCase
{
    use RefreshDatabase;

    public function test_quick_report_is_understood_end_to_end_without_changing_domicile(): void
    {
        [$identityRt, $incidentRt] = $this->createDifferentTerritories();
        $citizen = $this->createCitizen($identityRt);
        $citizenBeforeInterpretation = DB::table('citizens')->find($citizen->id);

        $result = $this->interpret(
            'jalan depan rumah rusak',
            $citizen,
            incidentRt: $incidentRt,
        );

        $this->assertSame(CitizenIntent::REPORT, $result->ruleBasedResolution->resolution->intent);
        $this->assertSame(UrgencyLevel::NORMAL, $result->ruleBasedResolution->resolution->urgency);
        $this->assertSame(IntentRule::REPORT_ROAD_DAMAGE, $result->ruleBasedResolution->matchedRule);
        $this->assertTrue($result->serviceUnderstanding->contextResult->context->identityRt?->is($identityRt));
        $this->assertSame('jalan depan rumah rusak', $result->serviceUnderstanding->contextResult->context->message);
        $this->assertTrue($result->ruleBasedResolution->resolution->incidentRt?->is($incidentRt));
        $this->assertTrue($result->serviceUnderstanding->serviceTerritoryDecision->preferredRt?->is($incidentRt));
        $this->assertSame(TerritoryPurpose::INCIDENT, $result->serviceUnderstanding->serviceTerritoryDecision->preferredSource);
        $this->assertReady($result);
        $this->assertEquals($citizenBeforeInterpretation, DB::table('citizens')->find($citizen->id));
        $this->assertDatabaseCount('reports', 0);
    }

    public function test_high_priority_report_is_ready_without_sla_or_escalation(): void
    {
        [$identityRt, $incidentRt] = $this->createDifferentTerritories();
        $citizen = $this->createCitizen($identityRt);

        $result = $this->interpret(
            'pohon tumbang menutup jalan',
            $citizen,
            incidentRt: $incidentRt,
        );

        $this->assertSame(CitizenIntent::REPORT, $result->ruleBasedResolution->resolution->intent);
        $this->assertSame(UrgencyLevel::HIGH, $result->ruleBasedResolution->resolution->urgency);
        $this->assertTrue($result->serviceUnderstanding->serviceTerritoryDecision->preferredRt?->is($incidentRt));
        $this->assertReady($result);
        $this->assertDatabaseCount('reports', 0);
    }

    public function test_cross_territory_emergency_preserves_all_facts_and_is_ready(): void
    {
        [$identityRt, $incidentRt] = $this->createDifferentTerritories();
        $citizen = $this->createCitizen($identityRt);
        $citizenBeforeInterpretation = DB::table('citizens')->find($citizen->id);
        $identityRtBeforeInterpretation = DB::table('rts')->find($identityRt->id);
        $incidentRtBeforeInterpretation = DB::table('rts')->find($incidentRt->id);

        $result = $this->interpret(
            'tolong panggil ambulans, ada orang pingsan',
            $citizen,
            entryRt: $incidentRt,
            incidentRt: $incidentRt,
        );
        $context = $result->serviceUnderstanding->contextResult->context;
        $territoryDecision = $result->serviceUnderstanding->serviceTerritoryDecision;

        $this->assertSame(CitizenIntent::EMERGENCY, $result->ruleBasedResolution->resolution->intent);
        $this->assertSame(UrgencyLevel::EMERGENCY, $result->ruleBasedResolution->resolution->urgency);
        $this->assertSame(IntentRule::EMERGENCY_DIRECT_AMBULANCE_REQUEST, $result->ruleBasedResolution->matchedRule);
        $this->assertSame(CitizenIntent::EMERGENCY, $result->ruleBasedResolution->matchedCategory);
        $this->assertSame('tolong panggil ambulans', $result->ruleBasedResolution->matchedPhrase);
        $this->assertTrue($context->identityRt?->is($identityRt));
        $this->assertTrue($context->entryRt?->is($incidentRt));
        $this->assertTrue($context->hasTerritoryConflict());
        $this->assertTrue($result->ruleBasedResolution->resolution->incidentRt?->is($incidentRt));
        $this->assertTrue($territoryDecision->preferredRt?->is($incidentRt));
        $this->assertSame(TerritoryPurpose::INCIDENT, $territoryDecision->preferredSource);
        $this->assertTrue($result->serviceUnderstanding->isTerritoryConflictClarifiedByIncident());
        $this->assertReady($result);
        $this->assertEquals($citizenBeforeInterpretation, DB::table('citizens')->find($citizen->id));
        $this->assertEquals($identityRtBeforeInterpretation, DB::table('rts')->find($identityRt->id));
        $this->assertEquals($incidentRtBeforeInterpretation, DB::table('rts')->find($incidentRt->id));
        $this->assertDatabaseCount('reports', 0);
    }

    public function test_ambulance_contact_question_remains_information_and_ready(): void
    {
        $identityRt = $this->createRt($this->createRw('001'), '001');
        $citizen = $this->createCitizen($identityRt);

        $result = $this->interpret('nomor ambulans desa berapa', $citizen);

        $this->assertSame(CitizenIntent::INFORMATION, $result->ruleBasedResolution->resolution->intent);
        $this->assertSame(UrgencyLevel::NORMAL, $result->ruleBasedResolution->resolution->urgency);
        $this->assertSame(IntentRule::INFORMATION_AMBULANCE_CONTACT, $result->ruleBasedResolution->matchedRule);
        $this->assertSame(ServiceTerritoryStatus::OPTIONAL, $result->serviceUnderstanding->serviceTerritoryDecision->status);
        $this->assertReady($result);
    }

    public function test_letter_uses_identity_territory_and_is_ready(): void
    {
        $identityRt = $this->createRt($this->createRw('001'), '001');
        $citizen = $this->createCitizen($identityRt);

        $result = $this->interpret('mau bikin surat domisili', $citizen);

        $this->assertSame(CitizenIntent::LETTER, $result->ruleBasedResolution->resolution->intent);
        $this->assertSame(UrgencyLevel::NORMAL, $result->ruleBasedResolution->resolution->urgency);
        $this->assertTrue($result->serviceUnderstanding->contextResult->context->identityRt?->is($identityRt));
        $this->assertTrue($result->serviceUnderstanding->serviceTerritoryDecision->preferredRt?->is($identityRt));
        $this->assertSame(TerritoryPurpose::IDENTITY, $result->serviceUnderstanding->serviceTerritoryDecision->preferredSource);
        $this->assertReady($result);
    }

    public function test_aspiration_is_not_misclassified_as_report(): void
    {
        $identityRt = $this->createRt($this->createRw('001'), '001');
        $citizen = $this->createCitizen($identityRt);

        $result = $this->interpret('saya usul dibuatkan lampu jalan', $citizen);

        $this->assertSame(CitizenIntent::ASPIRATION, $result->ruleBasedResolution->resolution->intent);
        $this->assertSame(UrgencyLevel::NORMAL, $result->ruleBasedResolution->resolution->urgency);
        $this->assertSame(IntentRule::ASPIRATION_PROPOSAL, $result->ruleBasedResolution->matchedRule);
        $this->assertNotSame(CitizenIntent::REPORT, $result->ruleBasedResolution->matchedCategory);
        $this->assertReady($result);
    }

    public function test_unknown_message_is_blocked_with_intent_unknown_reason(): void
    {
        $identityRt = $this->createRt($this->createRw('001'), '001');
        $citizen = $this->createCitizen($identityRt);

        $result = $this->interpret('halo', $citizen);

        $this->assertSame(CitizenIntent::UNKNOWN, $result->ruleBasedResolution->resolution->intent);
        $this->assertSame(UrgencyLevel::NORMAL, $result->ruleBasedResolution->resolution->urgency);
        $this->assertSame(IntentRule::UNKNOWN_NO_MATCH, $result->ruleBasedResolution->matchedRule);
        $this->assertBlockedFor($result, RoutingReadinessReason::INTENT_UNKNOWN);
    }

    public function test_negated_emergency_is_unknown_and_blocked(): void
    {
        $identityRt = $this->createRt($this->createRw('001'), '001');
        $citizen = $this->createCitizen($identityRt);

        $result = $this->interpret('tidak butuh ambulans', $citizen);

        $this->assertSame(CitizenIntent::UNKNOWN, $result->ruleBasedResolution->resolution->intent);
        $this->assertSame(UrgencyLevel::NORMAL, $result->ruleBasedResolution->resolution->urgency);
        $this->assertSame(IntentRule::UNKNOWN_NO_MATCH, $result->ruleBasedResolution->matchedRule);
        $this->assertBlockedFor($result, RoutingReadinessReason::INTENT_UNKNOWN);
        $this->assertDatabaseCount('reports', 0);
    }

    private function interpret(
        string $message,
        Citizen $citizen,
        ?Rt $entryRt = null,
        ?Rt $incidentRt = null,
    ): CitizenRequestUnderstanding {
        return app(CitizenRequestInterpreter::class)->interpret(
            new EntryContext(
                channel: 'web',
                message: 'Pesan lama yang harus diganti',
                phone: $citizen->phone_normalized,
                rt: $entryRt,
            ),
            $message,
            $incidentRt,
        );
    }

    private function assertReady(CitizenRequestUnderstanding $result): void
    {
        $this->assertSame(RoutingReadinessStatus::READY, $result->routingReadiness->status);
        $this->assertSame(RoutingReadinessReason::READY, $result->routingReadiness->reason);
        $this->assertTrue($result->routingReadiness->canProceed());
        $this->assertTrue($result->serviceUnderstanding->isIntentUrgencyValid());
    }

    private function assertBlockedFor(
        CitizenRequestUnderstanding $result,
        RoutingReadinessReason $reason,
    ): void {
        $this->assertSame(RoutingReadinessStatus::BLOCKED, $result->routingReadiness->status);
        $this->assertSame($reason, $result->routingReadiness->reason);
        $this->assertFalse($result->routingReadiness->canProceed());
    }

    private function createCitizen(Rt $rt): Citizen
    {
        return Citizen::factory()->for($rt)->create([
            'phone' => '081234567890',
            'phone_normalized' => '6281234567890',
        ]);
    }

    /** @return array{Rt, Rt} */
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
