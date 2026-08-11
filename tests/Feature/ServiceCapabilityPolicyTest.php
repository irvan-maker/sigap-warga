<?php

namespace Tests\Feature;

use App\Context\CitizenRequestUnderstanding;
use App\Context\EntryContext;
use App\Context\ServiceCapabilityDecision;
use App\Enums\CapabilityRequirement;
use App\Enums\HumanOversightRequirement;
use App\Enums\RoutingReadinessReason;
use App\Enums\ServiceActionType;
use App\Enums\ServiceExecutionEligibility;
use App\Enums\ServiceRouteTarget;
use App\Enums\ServiceTerritoryStatus;
use App\Enums\UrgencyLevel;
use App\Models\Citizen;
use App\Models\Rt;
use App\Models\Rw;
use App\Services\CitizenRequestInterpreter;
use App\Services\ServiceCapabilityPolicy;
use App\Services\ServiceRouter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ServiceCapabilityPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_normal_report_describes_create_case_capability(): void
    {
        [$identityRt, $incidentRt] = $this->createDifferentTerritories();
        $decision = $this->capability($this->interpret(
            'jalan depan rumah rusak',
            $this->createCitizen($identityRt),
            incidentRt: $incidentRt,
        ));

        $this->assertCapability(
            $decision,
            ServiceRouteTarget::REPORT_SERVICE,
            ServiceActionType::CREATE_CASE,
            CapabilityRequirement::REQUIRED,
            CapabilityRequirement::REQUIRED,
            HumanOversightRequirement::VERIFICATION,
        );
        $this->assertSame(UrgencyLevel::NORMAL, $decision->routingDecision->urgency);
    }

    public function test_high_priority_report_preserves_high_urgency(): void
    {
        [$identityRt, $incidentRt] = $this->createDifferentTerritories();
        $decision = $this->capability($this->interpret(
            'pohon tumbang menutup jalan',
            $this->createCitizen($identityRt),
            incidentRt: $incidentRt,
        ));

        $this->assertSame(ServiceActionType::CREATE_CASE, $decision->capability->actionType);
        $this->assertSame(UrgencyLevel::HIGH, $decision->routingDecision->urgency);
        $this->assertTrue($decision->isExecutable());
    }

    public function test_emergency_describes_distinct_operator_supervised_response_capability(): void
    {
        [$identityRt, $incidentRt] = $this->createDifferentTerritories();
        $decision = $this->capability($this->interpret(
            'tolong panggil ambulans, ada orang pingsan',
            $this->createCitizen($identityRt),
            entryRt: $incidentRt,
            incidentRt: $incidentRt,
        ));

        $this->assertCapability(
            $decision,
            ServiceRouteTarget::EMERGENCY_SERVICE,
            ServiceActionType::INITIATE_EMERGENCY_RESPONSE,
            CapabilityRequirement::OPTIONAL,
            CapabilityRequirement::REQUIRED,
            HumanOversightRequirement::OPERATOR_REQUIRED,
        );
        $this->assertNotSame(ServiceActionType::CREATE_CASE, $decision->capability->actionType);
        $this->assertTrue($decision->routingDecision->serviceTerritoryDecision->preferredRt?->is($incidentRt));
    }

    public function test_letter_describes_administrative_capability(): void
    {
        $identityRt = $this->createRt($this->createRw('001'), '001');
        $decision = $this->capability($this->interpret(
            'mau bikin surat domisili',
            $this->createCitizen($identityRt),
        ));

        $this->assertCapability(
            $decision,
            ServiceRouteTarget::LETTER_SERVICE,
            ServiceActionType::INITIATE_ADMINISTRATIVE_SERVICE,
            CapabilityRequirement::REQUIRED,
            CapabilityRequirement::REQUIRED,
            HumanOversightRequirement::APPROVAL,
        );
    }

    public function test_information_accepts_optional_identity_and_territory(): void
    {
        $identityRt = $this->createRt($this->createRw('001'), '001');
        $decision = $this->capability($this->interpret(
            'nomor ambulans desa berapa',
            $this->createCitizen($identityRt),
        ));

        $this->assertCapability(
            $decision,
            ServiceRouteTarget::INFORMATION_SERVICE,
            ServiceActionType::PROVIDE_INFORMATION,
            CapabilityRequirement::OPTIONAL,
            CapabilityRequirement::OPTIONAL,
            HumanOversightRequirement::NONE,
        );
        $this->assertSame(ServiceTerritoryStatus::OPTIONAL, $decision->routingDecision->serviceTerritoryDecision->status);
        $this->assertNull($decision->routingDecision->serviceTerritoryDecision->preferredRt);
    }

    public function test_aspiration_uses_conservative_registration_capability(): void
    {
        $identityRt = $this->createRt($this->createRw('001'), '001');
        $decision = $this->capability($this->interpret(
            'saya usul dibuatkan lampu jalan',
            $this->createCitizen($identityRt),
        ));

        $this->assertCapability(
            $decision,
            ServiceRouteTarget::ASPIRATION_SERVICE,
            ServiceActionType::REGISTER_ASPIRATION,
            CapabilityRequirement::REQUIRED,
            CapabilityRequirement::REQUIRED,
            HumanOversightRequirement::VERIFICATION,
        );
    }

    public function test_unknown_routes_to_non_executable_clarification_and_preserves_reason(): void
    {
        $identityRt = $this->createRt($this->createRw('001'), '001');
        $decision = $this->capability($this->interpret('halo', $this->createCitizen($identityRt)));

        $this->assertSame(ServiceRouteTarget::MANUAL_CLARIFICATION, $decision->capability->routeTarget);
        $this->assertSame(ServiceActionType::REQUEST_CLARIFICATION, $decision->capability->actionType);
        $this->assertSame(ServiceExecutionEligibility::NOT_EXECUTABLE, $decision->executionEligibility);
        $this->assertSame(RoutingReadinessReason::INTENT_UNKNOWN, $decision->reason);
        $this->assertSame($decision->routingDecision->reason, $decision->reason);
        $this->assertFalse($decision->isExecutable());
        $this->assertDatabaseCount('reports', 0);
    }

    public function test_cross_territory_emergency_preserves_citizen_and_territories_without_side_effects(): void
    {
        [$identityRt, $incidentRt] = $this->createDifferentTerritories();
        $citizen = $this->createCitizen($identityRt);
        $citizenBefore = DB::table('citizens')->find($citizen->id);
        $identityRtBefore = DB::table('rts')->find($identityRt->id);
        $incidentRtBefore = DB::table('rts')->find($incidentRt->id);

        $decision = $this->capability($this->interpret(
            'tolong panggil ambulans, ada orang pingsan',
            $citizen,
            entryRt: $incidentRt,
            incidentRt: $incidentRt,
        ));

        $this->assertSame(ServiceActionType::INITIATE_EMERGENCY_RESPONSE, $decision->capability->actionType);
        $this->assertTrue($decision->routingDecision->serviceTerritoryDecision->preferredRt?->is($incidentRt));
        $this->assertEquals($citizenBefore, DB::table('citizens')->find($citizen->id));
        $this->assertEquals($identityRtBefore, DB::table('rts')->find($identityRt->id));
        $this->assertEquals($incidentRtBefore, DB::table('rts')->find($incidentRt->id));
        $this->assertDatabaseCount('reports', 0);
    }

    private function capability(CitizenRequestUnderstanding $understanding): ServiceCapabilityDecision
    {
        $routingDecision = app(ServiceRouter::class)->route($understanding);

        return app(ServiceCapabilityPolicy::class)->decide($routingDecision);
    }

    private function interpret(
        string $message,
        Citizen $citizen,
        ?Rt $entryRt = null,
        ?Rt $incidentRt = null,
    ): CitizenRequestUnderstanding {
        return app(CitizenRequestInterpreter::class)->interpret(
            new EntryContext('web', $message, $citizen->phone_normalized, $entryRt),
            $message,
            $incidentRt,
        );
    }

    private function assertCapability(
        ServiceCapabilityDecision $decision,
        ServiceRouteTarget $target,
        ServiceActionType $actionType,
        CapabilityRequirement $identityRequirement,
        CapabilityRequirement $territoryRequirement,
        HumanOversightRequirement $oversight,
    ): void {
        $this->assertSame($target, $decision->capability->routeTarget);
        $this->assertSame($actionType, $decision->capability->actionType);
        $this->assertSame($identityRequirement, $decision->capability->identityRequirement);
        $this->assertSame($territoryRequirement, $decision->capability->serviceTerritoryRequirement);
        $this->assertSame($oversight, $decision->capability->humanOversight);
        $this->assertSame(ServiceExecutionEligibility::ELIGIBLE, $decision->executionEligibility);
        $this->assertTrue($decision->isExecutable());
        $this->assertDatabaseCount('reports', 0);
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
