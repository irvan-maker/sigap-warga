<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\VillagePosition;
use App\Models\Rt;
use App\Models\Rw;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_active_administrator_can_open_user_list(): void
    {
        $this->actingAs($this->createVillageOfficer(VillagePosition::SYSTEM_ADMIN))
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Kelola Akun Petugas');
    }

    public function test_user_index_follows_village_office_permission_matrix(): void
    {
        $this->get(route('admin.users.index'))->assertRedirect(route('login'));

        $rw = $this->createRw('001');
        $rt = $this->createRt($rw, '001');
        $allowedUsers = [
            $this->createVillageOfficer(VillagePosition::SYSTEM_ADMIN),
            $this->createVillageOfficer(VillagePosition::VILLAGE_SECRETARY),
        ];

        foreach ($allowedUsers as $user) {
            $this->actingAs($user)->get(route('admin.users.index'))->assertOk();
        }

        $deniedUsers = [
            $this->createVillageOfficer(VillagePosition::VILLAGE_HEAD),
            User::factory()->create([
                'role' => UserRole::RW,
                'position' => null,
                'rw_id' => $rw->id,
                'rt_id' => null,
            ]),
            User::factory()->create([
                'role' => UserRole::RT,
                'position' => null,
                'rw_id' => $rw->id,
                'rt_id' => $rt->id,
            ]),
            $this->createVillageOfficer(VillagePosition::SYSTEM_ADMIN, false),
        ];

        foreach ($deniedUsers as $user) {
            $this->actingAs($user)->get(route('admin.users.index'))->assertForbidden();
        }
    }

    public function test_user_mutations_follow_village_office_permission_matrix(): void
    {
        $rw = $this->createRw('001');
        $rt = $this->createRt($rw, '001');
        $secretary = $this->createVillageOfficer(VillagePosition::VILLAGE_SECRETARY);
        $allowedCases = [
            [UserRole::RW, $rw->id, null],
            [UserRole::RT, $rw->id, $rt->id],
        ];

        foreach ($allowedCases as $index => [$role, $rwId, $rtId]) {
            $email = "secretary-allowed{$index}@example.test";

            $this->actingAs($secretary)
                ->post(route('admin.users.store'), $this->validPayload($email, $role, $rwId, $rtId))
                ->assertRedirect();

            $this->assertDatabaseHas('users', ['email' => $email]);
        }

        $deniedCases = [
            [UserRole::ADMIN, VillagePosition::SYSTEM_ADMIN],
            [UserRole::KELURAHAN, VillagePosition::VILLAGE_HEAD],
            [UserRole::KELURAHAN, VillagePosition::VILLAGE_SECRETARY],
        ];

        foreach ($deniedCases as $index => [$role, $position]) {
            $email = "secretary-denied{$index}@example.test";

            $this->actingAs($secretary)
                ->post(route('admin.users.store'), $this->validPayload(
                    $email,
                    $role,
                    null,
                    null,
                    $position,
                ))
                ->assertSessionHasErrors('role');

            $this->assertDatabaseMissing('users', ['email' => $email]);
        }
    }

    public function test_search_and_role_and_status_filters_work(): void
    {
        $rw = $this->createRw('001');
        $target = User::factory()->create([
            'name' => 'Petugas Anggrek',
            'email' => 'anggrek@example.test',
            'role' => UserRole::RW,
            'rw_id' => $rw->id,
            'rt_id' => null,
            'is_active' => false,
        ]);
        $other = User::factory()->create(['name' => 'Administrator Lain']);

        $this->actingAs($this->createVillageOfficer(VillagePosition::SYSTEM_ADMIN))
            ->get(route('admin.users.index', [
                'search' => 'anggrek',
                'role' => UserRole::RW->value,
                'status' => 'inactive',
            ]))
            ->assertOk()
            ->assertSee($target->email)
            ->assertDontSee($other->name);
    }

    public function test_user_list_is_paginated_and_keeps_filters(): void
    {
        User::factory()->count(16)->create();

        $this->actingAs($this->createVillageOfficer(VillagePosition::SYSTEM_ADMIN))
            ->get(route('admin.users.index', ['role' => UserRole::ADMIN->value]))
            ->assertOk()
            ->assertViewHas('users', fn ($users): bool => $users->count() === 15
                && $users->total() === 17
                && $users->lastPage() === 2
            )
            ->assertSee('role=ADMIN', false);
    }

    public function test_administrator_can_create_every_role_with_valid_placement(): void
    {
        $admin = $this->createVillageOfficer(VillagePosition::SYSTEM_ADMIN);
        $rw = $this->createRw('001');
        $rt = $this->createRt($rw, '001');
        $cases = [
            ['role' => UserRole::ADMIN, 'position' => VillagePosition::SYSTEM_ADMIN, 'rw_id' => null, 'rt_id' => null],
            ['role' => UserRole::KELURAHAN, 'position' => VillagePosition::VILLAGE_HEAD, 'rw_id' => null, 'rt_id' => null],
            ['role' => UserRole::KELURAHAN, 'position' => VillagePosition::VILLAGE_SECRETARY, 'rw_id' => null, 'rt_id' => null],
            ['role' => UserRole::RW, 'position' => null, 'rw_id' => $rw->id, 'rt_id' => null],
            ['role' => UserRole::RT, 'position' => null, 'rw_id' => $rw->id, 'rt_id' => $rt->id],
        ];

        foreach ($cases as $index => $case) {
            $email = "role{$index}@example.test";
            $payload = $this->validPayload(
                $email,
                $case['role'],
                $case['rw_id'],
                $case['rt_id'],
                $case['position'],
            );

            $this->actingAs($admin)
                ->post(route('admin.users.store'), $payload)
                ->assertRedirect();

            $created = User::query()->where('email', $email)->sole();
            $this->assertSame($case['role'], $created->role);
            $this->assertSame($case['position'], $created->position);
            $this->assertSame($case['rw_id'], $created->rw_id);
            $this->assertSame($case['rt_id'], $created->rt_id);
            $this->assertTrue(Hash::check($payload['password'], $created->password));
            $this->assertNotSame($payload['password'], $created->password);
        }
    }

    public function test_invalid_role_and_region_combinations_are_rejected(): void
    {
        $admin = $this->createVillageOfficer(VillagePosition::SYSTEM_ADMIN);
        $firstRw = $this->createRw('001');
        $secondRw = $this->createRw('002');
        $firstRt = $this->createRt($firstRw, '001');
        $secondRt = $this->createRt($secondRw, '001');
        $cases = [
            [UserRole::ADMIN, $firstRw->id, null, 'rw_id'],
            [UserRole::KELURAHAN, null, $firstRt->id, 'rt_id'],
            [UserRole::RW, null, null, 'rw_id'],
            [UserRole::RW, $firstRw->id, $firstRt->id, 'rt_id'],
            [UserRole::RT, $firstRw->id, null, 'rt_id'],
            [UserRole::RT, $firstRw->id, $secondRt->id, 'rt_id'],
        ];

        foreach ($cases as $index => [$role, $rwId, $rtId, $errorField]) {
            $email = "invalid{$index}@example.test";

            $this->actingAs($admin)
                ->post(route('admin.users.store'), $this->validPayload($email, $role, $rwId, $rtId))
                ->assertSessionHasErrors($errorField);

            $this->assertDatabaseMissing('users', ['email' => $email]);
        }

        $this->assertNotSame($firstRt->id, $secondRt->id);
    }

    public function test_administrator_can_edit_a_user(): void
    {
        $admin = $this->createVillageOfficer(VillagePosition::SYSTEM_ADMIN);
        $rw = $this->createRw('001');
        $user = User::factory()->create(['role' => UserRole::KELURAHAN]);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $user), [
                'name' => 'Koordinator RW',
                'email' => 'koordinator@example.test',
                'role' => UserRole::RW->value,
                'rw_id' => $rw->id,
                'rt_id' => null,
            ])
            ->assertRedirect(route('admin.users.edit', $user));

        $user->refresh();
        $this->assertSame('Koordinator RW', $user->name);
        $this->assertSame(UserRole::RW, $user->role);
        $this->assertSame($rw->id, $user->rw_id);
    }

    public function test_administrator_can_activate_and_deactivate_another_user(): void
    {
        $admin = $this->createVillageOfficer(VillagePosition::SYSTEM_ADMIN);
        $user = User::factory()->create();

        $this->actingAs($admin)
            ->patch(route('admin.users.status.toggle', $user))
            ->assertRedirect(route('admin.users.edit', $user));
        $this->assertFalse($user->fresh()->is_active);

        $this->actingAs($admin)
            ->patch(route('admin.users.status.toggle', $user))
            ->assertRedirect(route('admin.users.edit', $user));
        $this->assertTrue($user->fresh()->is_active);
    }

    public function test_administrator_cannot_deactivate_or_demote_itself(): void
    {
        $admin = $this->createVillageOfficer(VillagePosition::SYSTEM_ADMIN);

        $this->actingAs($admin)
            ->patch(route('admin.users.status.toggle', $admin))
            ->assertForbidden();

        $this->actingAs($admin)
            ->put(route('admin.users.update', $admin), [
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => UserRole::KELURAHAN->value,
                'rw_id' => null,
                'rt_id' => null,
            ])
            ->assertSessionHasErrors('role');

        $this->assertTrue($admin->fresh()->is_active);
        $this->assertSame(UserRole::ADMIN, $admin->fresh()->role);
    }

    public function test_password_reset_replaces_hash_and_new_credentials_work(): void
    {
        $admin = $this->createVillageOfficer(VillagePosition::SYSTEM_ADMIN);
        $user = User::factory()->create(['password' => Hash::make('OldPassword123!')]);
        $oldHash = $user->password;
        $newPassword = 'NewSecurePassword123!';

        $this->actingAs($admin)
            ->patch(route('admin.users.password.reset', $user), [
                'password' => $newPassword,
                'password_confirmation' => $newPassword,
            ])
            ->assertRedirect(route('admin.users.edit', $user));

        $user->refresh();
        $this->assertNotSame($oldHash, $user->password);
        $this->assertTrue(Hash::check($newPassword, $user->password));
        $this->assertFalse(Hash::check('OldPassword123!', $user->password));

        Auth::logout();
        $this->assertTrue(Auth::attempt(['email' => $user->email, 'password' => $newPassword]));
    }

    public function test_user_list_does_not_lazy_load_region_relations(): void
    {
        $rw = $this->createRw('001');
        User::factory()->create(['role' => UserRole::RW, 'rw_id' => $rw->id, 'rt_id' => null]);
        Model::preventLazyLoading();

        try {
            $this->actingAs($this->createVillageOfficer(VillagePosition::SYSTEM_ADMIN))
                ->get(route('admin.users.index'))
                ->assertOk();
        } finally {
            Model::preventLazyLoading(false);
        }
    }

    private function validPayload(
        string $email,
        UserRole $role,
        ?int $rwId,
        ?int $rtId,
        ?VillagePosition $position = null,
    ): array {
        return [
            'name' => "Pengguna {$role->value}",
            'email' => $email,
            'password' => 'SecurePassword123!',
            'password_confirmation' => 'SecurePassword123!',
            'role' => $role->value,
            'position' => $position?->value,
            'rw_id' => $rwId,
            'rt_id' => $rtId,
        ];
    }

    private function createRw(string $code): Rw
    {
        return Rw::query()->create(['code' => $code, 'name' => "RW {$code}"]);
    }

    private function createRt(Rw $rw, string $code): Rt
    {
        return Rt::query()->create(['rw_id' => $rw->id, 'code' => $code, 'name' => "RT {$code}"]);
    }

    private function createVillageOfficer(VillagePosition $position, bool $isActive = true): User
    {
        return User::factory()->create([
            'role' => $position === VillagePosition::SYSTEM_ADMIN
                ? UserRole::ADMIN
                : UserRole::KELURAHAN,
            'position' => $position,
            'is_active' => $isActive,
            'rw_id' => null,
            'rt_id' => null,
        ]);
    }
}
