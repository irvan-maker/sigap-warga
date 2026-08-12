<?php

namespace Tests\Feature;

use App\Models\Rt;
use App\Models\Rw;
use App\Models\ServiceHandoff;
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

    public function test_valid_entry_renders_mobile_gateway_and_all_service_choices(): void
    {
        $issued = app(ServiceEntryPointIssuer::class)->issue($this->createRt(), 'Pos Pelayanan');

        $this->get(route('service-gateway.show', ['entryToken' => $issued->token]))
            ->assertOk()
            ->assertSee('SIGAP WARGA')
            ->assertSee('RT 03')
            ->assertSee('RW 01')
            ->assertSee('Laporan')
            ->assertSee('Informasi')
            ->assertSee('Surat')
            ->assertSee('Aspirasi')
            ->assertSee('Darurat')
            ->assertSee('Wilayah ini merupakan pintu layanan')
            ->assertDontSee($issued->record->token_hash);
    }

    public function test_invalid_revoked_and_inactive_entries_return_not_found(): void
    {
        $issued = app(ServiceEntryPointIssuer::class)->issue($rt = $this->createRt());

        $this->get('/q/'.$rt->id)->assertNotFound();
        $this->get('/q/sep_'.str_repeat('a', 43))->assertNotFound();
        $issued->record->update(['revoked_at' => now()]);
        $this->get(route('service-gateway.show', ['entryToken' => $issued->token]))->assertNotFound();

        $issued->record->update(['revoked_at' => null]);
        $rt->update(['is_active' => false]);
        $this->get(route('service-gateway.show', ['entryToken' => $issued->token]))->assertNotFound();
    }

    public function test_whatsapp_action_issues_handoff_and_redirects_with_opaque_marker(): void
    {
        $entry = app(ServiceEntryPointIssuer::class)->issue($this->createRt());
        $response = $this->post(route('service-gateway.whatsapp', [
            'entryToken' => $entry->token,
        ]), ['service' => 'report']);

        $response->assertRedirect();
        $location = rawurldecode($response->headers->get('Location'));
        $this->assertStringStartsWith('https://wa.me/6281234567890?text=', $location);
        $this->assertMatchesRegularExpression('/\[SW:swh_[A-Za-z0-9_-]{43}\]/', $location);
        $this->assertStringNotContainsString($entry->record->token_hash, $location);
        $this->assertDatabaseCount('service_handoffs', 1);

        preg_match('/\[SW:(swh_[A-Za-z0-9_-]{43})\]/', $location, $matches);
        $handoff = ServiceHandoff::query()->firstOrFail();
        $this->assertSame(hash('sha256', $matches[1]), $handoff->token_hash);
        $this->assertNotContains($matches[1], $handoff->getAttributes(), true);
    }

    public function test_service_hint_is_validated_and_public_number_is_required(): void
    {
        $entry = app(ServiceEntryPointIssuer::class)->issue($this->createRt());
        $route = route('service-gateway.whatsapp', ['entryToken' => $entry->token]);

        $this->post($route, ['service' => 'unsupported'])->assertSessionHasErrors('service');
        $this->assertDatabaseCount('service_handoffs', 0);

        config(['services.whatsapp.public_number' => null]);
        $this->post($route, ['service' => 'report'])->assertStatus(503);
        $this->assertDatabaseCount('service_handoffs', 0);
    }

    public function test_all_service_choices_only_issue_non_authoritative_handoffs(): void
    {
        $entry = app(ServiceEntryPointIssuer::class)->issue($this->createRt());
        $route = route('service-gateway.whatsapp', ['entryToken' => $entry->token]);

        foreach (['report', 'information', 'letter', 'aspiration', 'emergency'] as $service) {
            $this->post($route, ['service' => $service])->assertRedirect();
        }

        $this->assertDatabaseCount('service_handoffs', 5);
        $this->assertDatabaseCount('inbound_requests', 0);
        $this->assertDatabaseCount('reports', 0);
        $this->assertDatabaseCount('citizens', 0);
    }

    private function createRt(): Rt
    {
        $rw = Rw::query()->create(['code' => '001', 'name' => 'RW 01']);

        return Rt::query()->create([
            'rw_id' => $rw->id,
            'code' => '003',
            'name' => 'RT 03',
        ]);
    }
}
