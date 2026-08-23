<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Rt;
use App\Models\Rw;
use App\Models\ServiceEntryPoint;
use App\Models\User;
use App\Services\ServiceEntryPointIssuer;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminServiceEntryPointTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_system_admin_can_issue_print_and_revoke_an_rt_qr(): void
    {
        $admin = User::factory()->create();
        $rt = $this->rt();

        $response = $this->actingAs($admin)->post(route('admin.service-entry-points.store'), [
            'rt_id' => $rt->id,
            'label' => 'Balai Warga RT 001',
        ]);

        $response->assertOk()
            ->assertSee('QR BARU')
            ->assertSee('Balai Warga RT 001')
            ->assertSee('data:image/svg+xml;base64', false);

        $entryPoint = ServiceEntryPoint::query()->sole();
        $response->assertDontSee($entryPoint->token_hash);
        preg_match('#href="([^"]+/q/sep_[A-Za-z0-9_-]{43})"#', $response->getContent(), $matches);
        $this->assertArrayHasKey(1, $matches);

        $this->get($matches[1])->assertOk();
        $this->actingAs($admin)
            ->patch(route('admin.service-entry-points.revoke', $entryPoint))
            ->assertRedirect(route('admin.service-entry-points.index'));
        $this->get($matches[1])->assertNotFound();
    }

    public function test_rt_user_cannot_manage_qr_entry_points(): void
    {
        $rt = $this->rt();
        $user = User::factory()->create([
            'role' => UserRole::RT,
            'rw_id' => $rt->rw_id,
            'rt_id' => $rt->id,
        ]);

        $this->actingAs($user)
            ->get(route('admin.service-entry-points.index'))
            ->assertForbidden();
        $this->actingAs($user)
            ->post(route('admin.service-entry-points.store'), ['rt_id' => $rt->id])
            ->assertForbidden();

        $this->assertDatabaseCount('service_entry_points', 0);
    }

    public function test_each_rt_can_have_only_one_active_qr_and_can_replace_it_after_revocation(): void
    {
        $admin = User::factory()->create();
        $rt = $this->rt();
        $payload = ['rt_id' => $rt->id, 'label' => 'Balai Warga RT 001'];

        $this->actingAs($admin)->post(route('admin.service-entry-points.store'), $payload)->assertOk();
        $first = ServiceEntryPoint::query()->sole();

        $this->actingAs($admin)
            ->post(route('admin.service-entry-points.store'), $payload)
            ->assertSessionHasErrors('rt_id');

        $this->assertSame(1, ServiceEntryPoint::query()->count());
        $this->assertSame(1, $rt->activeServiceEntryPoints()->count());

        $this->actingAs($admin)
            ->patch(route('admin.service-entry-points.revoke', $first))
            ->assertRedirect(route('admin.service-entry-points.index'));
        $this->actingAs($admin)->post(route('admin.service-entry-points.store'), $payload)->assertOk();

        $this->assertSame(2, ServiceEntryPoint::query()->count());
        $this->assertSame(1, $rt->activeServiceEntryPoints()->count());
    }

    public function test_database_constraint_rejects_active_qr_duplicate_even_outside_the_service(): void
    {
        $rt = $this->rt();
        app(ServiceEntryPointIssuer::class)->issue($rt);

        try {
            DB::table('service_entry_points')->insert([
                'token_hash' => hash('sha256', 'duplicate-active-qr'),
                'rt_id' => $rt->id,
                'label' => 'Bypass attempt',
                'is_active' => true,
                'revoked_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->fail('Database should reject a second active QR for one RT.');
        } catch (QueryException) {
            $this->assertSame(1, $rt->activeServiceEntryPoints()->count());
        }
    }

    private function rt(): Rt
    {
        $rw = Rw::query()->create(['code' => '001', 'name' => 'RW 001']);

        return Rt::query()->create(['rw_id' => $rw->id, 'code' => '001', 'name' => 'RT 001']);
    }
}
