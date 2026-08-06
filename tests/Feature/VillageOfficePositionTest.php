<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\VillagePosition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VillageOfficePositionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_all_village_positions_are_redirected_to_the_kelurahan_dashboard(): void
    {
        foreach (VillagePosition::cases() as $position) {
            $user = User::factory()->create(['role' => UserRole::KELURAHAN, 'position' => $position]);
            $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('kelurahan.dashboard'));
        }
    }

    public function test_village_head_is_read_only_for_administrative_mutations(): void
    {
        $head = User::factory()->create(['role' => UserRole::KELURAHAN, 'position' => VillagePosition::VILLAGE_HEAD]);
        $this->actingAs($head)->get(route('kelurahan.dashboard'))->assertOk();
        $this->actingAs($head)->get(route('kelurahan.rws.index'))->assertOk();
        $this->actingAs($head)->post(route('kelurahan.rws.store'), ['code' => '001', 'name' => 'RW 001'])->assertForbidden();
        $this->actingAs($head)->get(route('admin.users.index'))->assertForbidden();
    }

    public function test_secretary_cannot_change_or_reset_system_administrator(): void
    {
        $secretary = User::factory()->create(['role' => UserRole::KELURAHAN, 'position' => VillagePosition::VILLAGE_SECRETARY]);
        $administrator = User::factory()->create();
        $this->actingAs($secretary)->put(route('admin.users.update', $administrator), [])->assertForbidden();
        $this->actingAs($secretary)->patch(route('admin.users.password.reset', $administrator), [])->assertForbidden();
    }

    public function test_dashboard_uses_indonesian_position_labels(): void
    {
        $head = User::factory()->create(['role' => UserRole::KELURAHAN, 'position' => VillagePosition::VILLAGE_HEAD]);
        $this->actingAs($head)->get(route('kelurahan.dashboard'))->assertSee('Kepala Desa');
    }
}
