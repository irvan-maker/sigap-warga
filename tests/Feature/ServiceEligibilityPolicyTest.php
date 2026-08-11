<?php

namespace Tests\Feature;

use App\Context\CitizenRequestUnderstanding;
use App\Context\EntryContext;
use App\Context\ServiceEligibilityDecision;
use App\Enums\HumanOversightRequirement;
use App\Enums\MissingServiceRequirement;
use App\Enums\RoutingReadinessReason;
use App\Enums\ServiceEligibilityReason;
use App\Enums\ServiceEligibilityStatus;
use App\Enums\ServiceRouteTarget;
use App\Enums\ServiceRoutingStatus;
use App\Models\Citizen;
use App\Models\Rt;
use App\Models\Rw;
use App\Services\CitizenRequestInterpreter;
use App\Services\ServiceEligibilityPolicy;
use App\Services\ServiceRouter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ServiceEligibilityPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_information_without_identity_or_territory_is_eligible_and_routable(): void
    {
        $understanding = $this->interpret('kantor desa buka jam berapa?');

        $eligibility = $this->evaluate($understanding);
        $routing = app(ServiceRouter::class)->route($understanding);

        $this->assertEligible($eligibility, ServiceRouteTarget::INFORMATION_SERVICE);
        $this->assertSame(HumanOversightRequirement::NONE, $eligibility->capability?->humanOversight);
        $this->assertSame(ServiceRoutingStatus::ROUTABLE, $routing->status);
        $this->assertSame(ServiceRouteTarget::INFORMATION_SERVICE, $routing->target);
        $this->assertDatabaseCount('citizens', 0);
        $this->assertDatabaseCount('reports', 0);
    }

    public function test_information_with_known_identity_is_eligible(): void
    {
        $identityRt = $this->createRt($this->createRw('001'), '001');
        $citizen = $this->createCitizen($identityRt);

        $this->assertEligible(
            $this->evaluate($this->interpret('kantor desa buka jam berapa?', $citizen)),
            ServiceRouteTarget::INFORMATION_SERVICE,
        );
    }

    public function test_emergency_without_identity_with_incident_territory_is_eligible(): void
    {
        $incidentRt = $this->createRt($this->createRw('005'), '010');
        $understanding = $this->interpret(
            'tolong ambulans, ada orang pingsan',
            entryRt: $incidentRt,
            incidentRt: $incidentRt,
        );

        $eligibility = $this->evaluate($understanding);
        $routing = app(ServiceRouter::class)->route($understanding);

        $this->assertEligible($eligibility, ServiceRouteTarget::EMERGENCY_SERVICE);
        $this->assertSame(HumanOversightRequirement::OPERATOR_REQUIRED, $eligibility->capability?->humanOversight);
        $this->assertSame(ServiceRouteTarget::EMERGENCY_SERVICE, $routing->target);
        $this->assertTrue($routing->serviceTerritoryDecision->preferredRt?->is($incidentRt));
        $this->assertDatabaseCount('citizens', 0);
        $this->assertDatabaseCount('reports', 0);
    }

    public function test_emergency_without_identity_or_territory_is_blocked_for_territory_only(): void
    {
        $eligibility = $this->evaluate($this->interpret('tolong ambulans, ada orang pingsan'));

        $this->assertBlocked(
            $eligibility,
            ServiceEligibilityReason::TERRITORY_REQUIRED,
            MissingServiceRequirement::TERRITORY,
        );
        $this->assertSame(ServiceRouteTarget::EMERGENCY_SERVICE, $eligibility->routeTarget);
    }

    public function test_letter_without_identity_is_blocked_for_identity(): void
    {
        $eligibility = $this->evaluate($this->interpret('mau bikin surat domisili'));

        $this->assertBlocked(
            $eligibility,
            ServiceEligibilityReason::IDENTITY_REQUIRED,
            MissingServiceRequirement::IDENTITY,
        );
    }

    public function test_report_without_identity_with_incident_territory_is_blocked_for_identity(): void
    {
        $incidentRt = $this->createRt($this->createRw('005'), '010');
        $understanding = $this->interpret(
            'jalan depan rumah rusak',
            entryRt: $incidentRt,
            incidentRt: $incidentRt,
        );

        $eligibility = $this->evaluate($understanding);
        $routing = app(ServiceRouter::class)->route($understanding);

        $this->assertBlocked(
            $eligibility,
            ServiceEligibilityReason::IDENTITY_REQUIRED,
            MissingServiceRequirement::IDENTITY,
        );
        $this->assertSame(ServiceRoutingStatus::BLOCKED, $routing->status);
        $this->assertSame(RoutingReadinessReason::IDENTITY_REQUIRED, $routing->reason);
    }

    public function test_aspiration_without_identity_is_blocked_for_identity(): void
    {
        $entryRt = $this->createRt($this->createRw('005'), '010');
        $eligibility = $this->evaluate($this->interpret(
            'saya usul dibuatkan lampu jalan',
            entryRt: $entryRt,
        ));

        $this->assertBlocked(
            $eligibility,
            ServiceEligibilityReason::IDENTITY_REQUIRED,
            MissingServiceRequirement::IDENTITY,
        );
    }

    public function test_report_with_known_identity_and_service_territory_is_eligible(): void
    {
        [$identityRt, $incidentRt] = $this->createDifferentTerritories();
        $citizen = $this->createCitizen($identityRt);
        $eligibility = $this->evaluate($this->interpret(
            'jalan depan rumah rusak',
            $citizen,
            incidentRt: $incidentRt,
        ));

        $this->assertEligible($eligibility, ServiceRouteTarget::REPORT_SERVICE);
        $this->assertSame(HumanOversightRequirement::VERIFICATION, $eligibility->capability?->humanOversight);
    }

    public function test_cross_territory_emergency_preserves_incident_territory_and_domicile(): void
    {
        [$identityRt, $incidentRt] = $this->createDifferentTerritories();
        $citizen = $this->createCitizen($identityRt);
        $citizenBefore = DB::table('citizens')->find($citizen->id);
        $identityRtBefore = DB::table('rts')->find($identityRt->id);
        $incidentRtBefore = DB::table('rts')->find($incidentRt->id);
        $understanding = $this->interpret(
            'tolong ambulans, ada orang pingsan',
            $citizen,
            entryRt: $incidentRt,
            incidentRt: $incidentRt,
        );

        $eligibility = $this->evaluate($understanding);
        $routing = app(ServiceRouter::class)->route($understanding);

        $this->assertEligible($eligibility, ServiceRouteTarget::EMERGENCY_SERVICE);
        $this->assertTrue($routing->serviceTerritoryDecision->preferredRt?->is($incidentRt));
        $this->assertTrue($understanding->serviceUnderstanding->contextResult->context->identityRt?->is($identityRt));
        $this->assertEquals($citizenBefore, DB::table('citizens')->find($citizen->id));
        $this->assertEquals($identityRtBefore, DB::table('rts')->find($identityRt->id));
        $this->assertEquals($incidentRtBefore, DB::table('rts')->find($incidentRt->id));
        $this->assertDatabaseCount('reports', 0);
    }

    private function evaluate(CitizenRequestUnderstanding $understanding): ServiceEligibilityDecision
    {
        return app(ServiceEligibilityPolicy::class)->evaluate($understanding);
    }

    private function interpret(
        string $message,
        ?Citizen $citizen = null,
        ?Rt $entryRt = null,
        ?Rt $incidentRt = null,
    ): CitizenRequestUnderstanding {
        return app(CitizenRequestInterpreter::class)->interpret(
            new EntryContext(
                channel: 'web',
                message: $message,
                phone: $citizen?->phone_normalized ?? '6289999999999',
                rt: $entryRt,
            ),
            $message,
            $incidentRt,
        );
    }

    private function assertEligible(ServiceEligibilityDecision $decision, ServiceRouteTarget $target): void
    {
        $this->assertSame(ServiceEligibilityStatus::ELIGIBLE, $decision->status);
        $this->assertSame(ServiceEligibilityReason::ELIGIBLE, $decision->reason);
        $this->assertSame($target, $decision->routeTarget);
        $this->assertSame($target, $decision->capability?->routeTarget);
        $this->assertNull($decision->missingRequirement);
        $this->assertTrue($decision->isEligible());
    }

    private function assertBlocked(
        ServiceEligibilityDecision $decision,
        ServiceEligibilityReason $reason,
        MissingServiceRequirement $missingRequirement,
    ): void {
        $this->assertSame(ServiceEligibilityStatus::BLOCKED, $decision->status);
        $this->assertSame($reason, $decision->reason);
        $this->assertSame($missingRequirement, $decision->missingRequirement);
        $this->assertFalse($decision->isEligible());
        $this->assertDatabaseCount('citizens', 0);
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
