<?php

namespace Tests\Feature;

use App\Context\CitizenRequestUnderstanding;
use App\Context\EntryContext;
use App\Context\IntentResolution;
use App\Context\RuleBasedIntentResolution;
use App\Context\ServiceRoutingDecision;
use App\Enums\CitizenIntent;
use App\Enums\IntentRule;
use App\Enums\RoutingReadinessReason;
use App\Enums\ServiceRouteTarget;
use App\Enums\ServiceRoutingReason;
use App\Enums\ServiceRoutingStatus;
use App\Enums\ServiceTerritoryStatus;
use App\Enums\UrgencyLevel;
use App\Models\Citizen;
use App\Models\Rt;
use App\Models\Rw;
use App\Services\CitizenRequestInterpreter;
use App\Services\RoutingReadinessDiagnoser;
use App\Services\ServiceRouter;
use App\Services\ServiceUnderstandingOrchestrator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ServiceRouterTest extends TestCase
{
    use RefreshDatabase;

    public function test_quick_report_routes_to_report_service(): void
    {
        [$identityRt, $incidentRt] = $this->createDifferentTerritories();
        $citizen = $this->createCitizen($identityRt);

        $decision = $this->route($this->interpret('jalan depan rumah rusak', $citizen, incidentRt: $incidentRt));

        $this->assertRoutable($decision, ServiceRouteTarget::REPORT_SERVICE, ServiceRoutingReason::ROUTED_TO_REPORT);
        $this->assertSame(UrgencyLevel::NORMAL, $decision->urgency);
    }

    public function test_high_priority_report_preserves_high_urgency(): void
    {
        [$identityRt, $incidentRt] = $this->createDifferentTerritories();
        $citizen = $this->createCitizen($identityRt);

        $decision = $this->route($this->interpret('pohon tumbang menutup jalan', $citizen, incidentRt: $incidentRt));

        $this->assertRoutable($decision, ServiceRouteTarget::REPORT_SERVICE, ServiceRoutingReason::ROUTED_TO_REPORT);
        $this->assertSame(UrgencyLevel::HIGH, $decision->urgency);
    }

    public function test_cross_territory_emergency_preserves_identity_and_incident_territory_without_side_effects(): void
    {
        [$identityRt, $incidentRt] = $this->createDifferentTerritories();
        $citizen = $this->createCitizen($identityRt);
        $citizenBefore = DB::table('citizens')->find($citizen->id);
        $identityRtBefore = DB::table('rts')->find($identityRt->id);
        $incidentRtBefore = DB::table('rts')->find($incidentRt->id);
        $understanding = $this->interpret(
            'tolong panggil ambulans, ada orang pingsan',
            $citizen,
            entryRt: $incidentRt,
            incidentRt: $incidentRt,
        );

        $decision = $this->route($understanding);

        $this->assertRoutable($decision, ServiceRouteTarget::EMERGENCY_SERVICE, ServiceRoutingReason::ROUTED_TO_EMERGENCY);
        $this->assertSame(UrgencyLevel::EMERGENCY, $decision->urgency);
        $this->assertSame($understanding->serviceUnderstanding->serviceTerritoryDecision, $decision->serviceTerritoryDecision);
        $this->assertTrue($decision->serviceTerritoryDecision->preferredRt?->is($incidentRt));
        $this->assertTrue($understanding->serviceUnderstanding->contextResult->context->identityRt?->is($identityRt));
        $this->assertEquals($citizenBefore, DB::table('citizens')->find($citizen->id));
        $this->assertEquals($identityRtBefore, DB::table('rts')->find($identityRt->id));
        $this->assertEquals($incidentRtBefore, DB::table('rts')->find($incidentRt->id));
        $this->assertDatabaseCount('reports', 0);
    }

    public function test_letter_routes_to_letter_service(): void
    {
        $identityRt = $this->createRt($this->createRw('001'), '001');
        $decision = $this->route($this->interpret('mau bikin surat domisili', $this->createCitizen($identityRt)));

        $this->assertRoutable($decision, ServiceRouteTarget::LETTER_SERVICE, ServiceRoutingReason::ROUTED_TO_LETTER);
        $this->assertTrue($decision->serviceTerritoryDecision->preferredRt?->is($identityRt));
    }

    public function test_information_routes_without_forcing_optional_territory(): void
    {
        $identityRt = $this->createRt($this->createRw('001'), '001');
        $decision = $this->route($this->interpret('nomor ambulans desa berapa', $this->createCitizen($identityRt)));

        $this->assertRoutable($decision, ServiceRouteTarget::INFORMATION_SERVICE, ServiceRoutingReason::ROUTED_TO_INFORMATION);
        $this->assertSame(ServiceTerritoryStatus::OPTIONAL, $decision->serviceTerritoryDecision->status);
        $this->assertNull($decision->serviceTerritoryDecision->preferredRt);
    }

    public function test_aspiration_routes_to_aspiration_service(): void
    {
        $identityRt = $this->createRt($this->createRw('001'), '001');
        $decision = $this->route($this->interpret('saya usul dibuatkan lampu jalan', $this->createCitizen($identityRt)));

        $this->assertRoutable($decision, ServiceRouteTarget::ASPIRATION_SERVICE, ServiceRoutingReason::ROUTED_TO_ASPIRATION);
    }

    public function test_unknown_is_blocked_and_preserves_readiness_reason(): void
    {
        $identityRt = $this->createRt($this->createRw('001'), '001');
        $decision = $this->route($this->interpret('halo', $this->createCitizen($identityRt)));

        $this->assertBlockedFor($decision, RoutingReadinessReason::INTENT_UNKNOWN);
    }

    public function test_missing_identity_is_blocked(): void
    {
        $entryRt = $this->createRt($this->createRw('001'), '001');
        $decision = $this->route($this->interpretWithoutCitizen('nomor ambulans desa berapa', entryRt: $entryRt));

        $this->assertBlockedFor($decision, RoutingReadinessReason::IDENTITY_REQUIRED);
    }

    public function test_territory_confirmation_required_is_blocked(): void
    {
        [$identityRt, $entryRt] = $this->createDifferentTerritories();
        $citizen = $this->createCitizen($identityRt);
        $decision = $this->route($this->interpret('jalan depan rumah rusak', $citizen, entryRt: $entryRt));

        $this->assertBlockedFor($decision, RoutingReadinessReason::TERRITORY_CONFIRMATION_REQUIRED);
    }

    public function test_invalid_intent_urgency_is_blocked(): void
    {
        [$identityRt, $incidentRt] = $this->createDifferentTerritories();
        $citizen = $this->createCitizen($identityRt);
        $resolution = new IntentResolution(CitizenIntent::REPORT, UrgencyLevel::EMERGENCY, $incidentRt);
        $serviceUnderstanding = app(ServiceUnderstandingOrchestrator::class)->understand(
            new EntryContext('web', 'laporan darurat', $citizen->phone_normalized),
            $resolution,
        );
        $understanding = new CitizenRequestUnderstanding(
            new RuleBasedIntentResolution($resolution, IntentRule::REPORT_ROAD_DAMAGE, CitizenIntent::REPORT),
            $serviceUnderstanding,
            app(RoutingReadinessDiagnoser::class)->diagnose($serviceUnderstanding),
        );

        $this->assertBlockedFor($this->route($understanding), RoutingReadinessReason::INTENT_URGENCY_INVALID);
    }

    private function route(CitizenRequestUnderstanding $understanding): ServiceRoutingDecision
    {
        return app(ServiceRouter::class)->route($understanding);
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

    private function interpretWithoutCitizen(string $message, ?Rt $entryRt = null): CitizenRequestUnderstanding
    {
        return app(CitizenRequestInterpreter::class)->interpret(
            new EntryContext('web', $message, '6289999999999', $entryRt),
            $message,
        );
    }

    private function assertRoutable($decision, ServiceRouteTarget $target, ServiceRoutingReason $reason): void
    {
        $this->assertSame(ServiceRoutingStatus::ROUTABLE, $decision->status);
        $this->assertSame($target, $decision->target);
        $this->assertSame($reason, $decision->reason);
        $this->assertTrue($decision->canRoute());
        $this->assertDatabaseCount('reports', 0);
    }

    private function assertBlockedFor($decision, RoutingReadinessReason $reason): void
    {
        $this->assertSame(ServiceRoutingStatus::BLOCKED, $decision->status);
        $this->assertSame(ServiceRouteTarget::MANUAL_CLARIFICATION, $decision->target);
        $this->assertSame($reason, $decision->reason);
        $this->assertFalse($decision->canRoute());
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
