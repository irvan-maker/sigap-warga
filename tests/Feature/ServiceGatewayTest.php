<?php

namespace Tests\Feature;

use App\Models\Rt;
use App\Models\Rw;
use App\Services\ServiceEntryPointIssuer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceGatewayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.whatsapp.public_number' => '6281234567890']);
    }

    public function test_valid_entry_renders_quick_report_gateway_and_prototype_module_notes(): void
    {
        $issued = app(ServiceEntryPointIssuer::class)->issue(
            $this->createRt(),
            'Pos Pelayanan'
        );

        $this->get(route('service-gateway.show', [
            'entryToken' => $issued->token,
        ]))
            ->assertOk()
            ->assertSee('SIGAP WARGA')
            ->assertSee('RT 03')
            ->assertSee('RW 01')
            ->assertSee('Laporan Cepat')
            ->assertSee('Sensus')
            ->assertSee('Posyandu')
            ->assertSee('Persuratan')
            ->assertSee('Wilayah ini merupakan pintu layanan')
            ->assertDontSee($issued->record->token_hash);
    }

    public function test_invalid_revoked_and_inactive_entries_return_not_found(): void
    {
        $issued = app(ServiceEntryPointIssuer::class)->issue(
            $rt = $this->createRt()
        );

        $this->get('/q/'.$rt->id)->assertNotFound();
        $this->get('/q/sep_'.str_repeat('a', 43))->assertNotFound();

        $issued->record->update(['revoked_at' => now()]);

        $this->get(route('service-gateway.show', [
            'entryToken' => $issued->token,
        ]))->assertNotFound();

        $issued->record->update(['revoked_at' => null]);
        $rt->update(['is_active' => false]);

        $this->get(route('service-gateway.show', [
            'entryToken' => $issued->token,
        ]))->assertNotFound();

        $rt->update(['is_active' => true]);
        $rt->rw->update(['is_active' => false]);

        $this->get(route('service-gateway.show', [
            'entryToken' => $issued->token,
        ]))->assertNotFound();
    }

    public function test_whatsapp_action_uses_a_trusted_one_time_handoff_token(): void
    {
        $entry = app(ServiceEntryPointIssuer::class)->issue(
            $this->createRt()
        );

        $response = $this->post(
            route('service-gateway.whatsapp', [
                'entryToken' => $entry->token,
            ]),
            ['privacy_acknowledged' => '1']
        );

        $response->assertRedirect();

        $location = rawurldecode(
            $response->headers->get('Location')
        );

        $this->assertStringStartsWith(
            'https://wa.me/6281234567890?text=',
            $location
        );

        $this->assertStringContainsString('[SW:swh_', $location);
        $this->assertStringContainsString(
            'MULAI LAPORAN SIGAP WARGA',
            $location
        );
        $this->assertStringContainsString(
            "Pintu layanan:\n003 / 001",
            $location
        );

        $this->assertStringNotContainsString(
            $entry->record->token_hash,
            $location
        );

        $this->assertDatabaseCount('service_handoffs', 1);

        $this->assertDatabaseHas('service_handoffs', [
            'service_entry_point_id' => $entry->record->id,
        ]);
    }

    public function test_public_number_is_required(): void
    {
        $entry = app(ServiceEntryPointIssuer::class)->issue(
            $this->createRt()
        );

        $route = route('service-gateway.whatsapp', [
            'entryToken' => $entry->token,
        ]);

        config(['services.whatsapp.public_number' => null]);

        $this->post(
            $route,
            ['privacy_acknowledged' => '1']
        )->assertStatus(503);

        $this->assertDatabaseCount('service_handoffs', 0);
    }

    public function test_repeated_whatsapp_clicks_create_independent_handoffs_only(): void
    {
        $entry = app(ServiceEntryPointIssuer::class)->issue(
            $this->createRt()
        );

        $route = route('service-gateway.whatsapp', [
            'entryToken' => $entry->token,
        ]);

        foreach (range(1, 5) as $_) {
            $this->post(
                $route,
                ['privacy_acknowledged' => '1']
            )->assertRedirect();
        }

        $this->assertDatabaseCount('service_handoffs', 5);
        $this->assertDatabaseCount('inbound_requests', 0);
        $this->assertDatabaseCount('reports', 0);
        $this->assertDatabaseCount('citizens', 0);
    }

    public function test_privacy_acknowledgement_is_required_before_opening_whatsapp(): void
    {
        $entry = app(ServiceEntryPointIssuer::class)->issue(
            $this->createRt()
        );

        $this->post(route('service-gateway.whatsapp', [
            'entryToken' => $entry->token,
        ]))->assertSessionHasErrors('privacy_acknowledged');
    }

    private function createRt(): Rt
    {
        $rw = Rw::query()->create([
            'code' => '001',
            'name' => 'RW 01',
        ]);

        return Rt::query()->create([
            'rw_id' => $rw->id,
            'code' => '003',
            'name' => 'RT 03',
        ]);
    }
}
