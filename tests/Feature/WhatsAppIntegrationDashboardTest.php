<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\VillagePosition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WhatsAppIntegrationDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_guest_is_redirected_and_non_system_admin_is_forbidden(): void
    {
        $this->get(route('admin.whatsapp-integration.index'))->assertRedirect(route('login'));

        $officer = User::factory()->create([
            'role' => UserRole::KELURAHAN,
            'position' => VillagePosition::VILLAGE_SECRETARY,
        ]);

        $this->actingAs($officer)
            ->get(route('admin.whatsapp-integration.index'))
            ->assertForbidden();
    }

    public function test_system_admin_sees_safe_status_and_setup_guide_without_secret_values(): void
    {
        config([
            'app.env' => 'production',
            'app.debug' => false,
            'app.url' => 'https://sigap.example.test',
            'queue.default' => 'database',
            'services.whatsapp.webhook_verify_token' => str_repeat('verify-secret-', 4),
            'services.whatsapp.app_secret' => 'meta-app-secret-never-render',
            'services.whatsapp.public_number' => '6281234567890',
            'services.whatsapp.waba_id' => '100000000000001',
            'services.whatsapp.phone_number_id' => '100000000000002',
            'services.whatsapp.access_token' => 'meta-access-token-never-render',
            'services.whatsapp.graph_version' => 'v23.0',
            'services.whatsapp.outbound_enabled' => false,
        ]);

        $response = $this->actingAs(User::factory()->create())
            ->get(route('admin.whatsapp-integration.index'))
            ->assertOk()
            ->assertSee('Hubungkan Meta WhatsApp dengan aman')
            ->assertSee('Urutan pendaftaran Meta')
            ->assertSee('WHATSAPP_WABA_ID')
            ->assertSee('/webhooks/whatsapp')
            ->assertDontSee('meta-app-secret-never-render')
            ->assertDontSee('meta-access-token-never-render')
            ->assertDontSee(str_repeat('verify-secret-', 4));

        $response->assertViewHas('checks', fn ($checks): bool => $checks->firstWhere('key', 'waba_id')['ready']
            && $checks->firstWhere('key', 'phone_number_id')['ready']
            && $checks->firstWhere('key', 'app_origin')['ready']
            && ! $checks->firstWhere('key', 'outbound')['ready']);
    }

    public function test_dashboard_rejects_dashboard_path_as_app_url_origin(): void
    {
        config(['app.url' => 'https://sigap.example.test/kelurahan/dashboard']);

        $this->actingAs(User::factory()->create())
            ->get(route('admin.whatsapp-integration.index'))
            ->assertOk()
            ->assertSee('Isi domain saja, tanpa /kelurahan/dashboard')
            ->assertViewHas('checks', fn ($checks): bool => ! $checks->firstWhere('key', 'app_origin')['ready']);
    }
}
