<?php

namespace Tests\Feature;

use App\Enums\FamilyRelationship;
use App\Enums\UserRole;
use App\Enums\VillagePosition;
use App\Models\Citizen;
use App\Models\FamilyCard;
use App\Models\Rt;
use App\Models\Rw;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HouseholdCensusTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_only_active_rt_can_open_household_census(): void
    {
        [$rtUser, $rt] = $this->region();
        $rwUser = User::factory()->create(['role' => UserRole::RW, 'position' => null, 'rw_id' => $rt->rw_id, 'rt_id' => null]);
        $head = User::factory()->create(['role' => UserRole::KELURAHAN, 'position' => VillagePosition::VILLAGE_HEAD, 'rw_id' => null, 'rt_id' => null]);

        $this->actingAs($rtUser)->get(route('rt.household-census.create'))->assertOk()->assertSee('Sensus Keluarga')->assertDontSee('name="rt_id"', false)->assertDontSee('name="rw_id"', false);
        $this->actingAs($rwUser)->get('/rt/household-census/create')->assertForbidden();
        $this->actingAs($head)->get('/rt/household-census/create')->assertForbidden();
        auth()->logout();
        $this->post(route('rt.household-census.store'), $this->payload())->assertRedirect(route('login'));
    }

    public function test_submit_creates_consistent_card_head_and_members_and_ignores_region_payload(): void
    {
        [$user, $rt, $otherRt] = $this->region();

        $response = $this->actingAs($user)->post(route('rt.household-census.store'), [
            ...$this->payload(),
            'rt_id' => $otherRt->id,
            'rw_id' => $otherRt->rw_id,
            'family_card_id' => 999,
            'head_citizen_id' => 999,
        ]);

        $card = FamilyCard::query()->sole();
        $response->assertRedirect(route('rt.family-cards.show', $card))->assertSessionHas('status', 'Sensus keluarga berhasil disimpan.');
        $this->assertSame($rt->id, $card->rt_id);
        $this->assertCount(3, $card->citizens);
        $head = $card->headCitizen;
        $this->assertSame(FamilyRelationship::HEAD, $head->family_relationship);
        $this->assertSame('Kepala Sensus', $head->name);
        foreach ($card->citizens as $citizen) {
            $this->assertSame($rt->id, $citizen->rt_id);
            $this->assertSame($card->id, $citizen->family_card_id);
            $this->assertSame('Jalan Sensus 10', $citizen->address);
        }
        $this->assertSame(2, Citizen::query()->whereNull('nik')->count());
    }

    public function test_duplicate_identifiers_and_invalid_member_roll_back_everything_and_preserve_old_input(): void
    {
        [$user, $rt] = $this->region();
        FamilyCard::factory()->for($rt)->create(['family_number' => '3201010101010199']);
        Citizen::factory()->for($rt)->create(['nik' => '3201010101010188']);
        $payload = $this->payload();
        $payload['family_number'] = '3201010101010199';
        $payload['members'][0]['nik'] = '123';
        $payload['members'][0]['name'] = 'Nama Tetap';

        $this->actingAs($user)->from(route('rt.household-census.create'))->post(route('rt.household-census.store'), $payload)
            ->assertRedirect(route('rt.household-census.create'))
            ->assertSessionHasErrors(['family_number', 'members.0.nik'])
            ->assertSessionHasInput('members.0.name', 'Nama Tetap');
        $this->assertDatabaseCount('family_cards', 1);
        $this->assertDatabaseCount('citizens', 1);

        $payload = $this->payload();
        $payload['head']['nik'] = '3201010101010188';
        $this->actingAs($user)->post(route('rt.household-census.store'), $payload)->assertSessionHasErrors('head.nik');
        $this->assertDatabaseCount('family_cards', 1);
    }

    public function test_completely_empty_member_is_discarded(): void
    {
        [$user] = $this->region();
        $payload = $this->payload();
        $payload['members'] = [['name' => '', 'nik' => '', 'family_relationship' => '']];
        $this->actingAs($user)->post(route('rt.household-census.store'), $payload)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('citizens', 1);
    }

    public function test_rt_dashboard_has_two_master_cards_and_no_inline_onclick(): void
    {
        [$user] = $this->region();
        $response = $this->actingAs($user)->get(route('rt.dashboard'))->assertOk();
        $response->assertSee('Sensus Warga')->assertSee(route('rt.household-census.create'))->assertSee('Kelengkapan Data')->assertDontSee('onclick=', false);
        $html = $response->getContent();
        $master = substr($html, strpos($html, 'id="master-data-heading"'), strpos($html, 'id="kpi-heading"') - strpos($html, 'id="master-data-heading"'));
        $this->assertSame(2, substr_count($master, 'class="col-lg-6"'));
        $this->assertStringNotContainsString('Kartu Keluarga', $master);
    }

    public function test_census_polish_renders_progress_summary_and_member_empty_state(): void
    {
        [$user] = $this->region();

        $this->actingAs($user)->get(route('rt.household-census.create'))
            ->assertOk()
            ->assertSee('Data Kartu Keluarga')
            ->assertSee('Nomor KK menjadi identitas seluruh anggota keluarga.')
            ->assertSee('KEPALA KELUARGA')
            ->assertSee('Ringkasan')
            ->assertSee('Jumlah warga yang akan dibuat')
            ->assertSee('Belum ada anggota keluarga')
            ->assertSee('aria-label="Tambah anggota keluarga"', false)
            ->assertDontSee('onclick=', false);
    }

    public function test_old_members_are_numbered_and_errors_remain_below_invalid_fields(): void
    {
        [$user] = $this->region();
        $payload = $this->payload();
        $payload['members'][0]['name'] = '';

        $this->actingAs($user)->from(route('rt.household-census.create'))->followingRedirects()->post(route('rt.household-census.store'), $payload)
            ->assertOk()
            ->assertSee('aria-label="Hapus anggota 1"', false)
            ->assertSee('aria-label="Hapus anggota 2"', false)
            ->assertSee('is-invalid', false)
            ->assertSee('Nama lengkap anggota wajib diisi.');
    }

    public function test_census_javascript_uses_delegation_for_add_remove_summary_and_error_scroll(): void
    {
        $javascript = file_get_contents(resource_path('js/app.js'));

        $this->assertStringContainsString("census.addEventListener('click'", $javascript);
        $this->assertStringContainsString("event.target.closest('[data-add-member]')", $javascript);
        $this->assertStringContainsString("event.target.closest('[data-remove-member]')", $javascript);
        $this->assertStringContainsString("census.addEventListener('input', updateSummary)", $javascript);
        $this->assertStringContainsString('firstInvalid.scrollIntoView', $javascript);
        $this->assertStringNotContainsString('onclick=', $javascript);
    }

    private function payload(): array
    {
        return [
            'family_number' => '3201010101010101',
            'address' => 'Jalan Sensus 10',
            'head' => ['name' => 'Kepala Sensus', 'nik' => '3201010101010102', 'phone' => null, 'birth_place' => null, 'birth_date' => null, 'gender' => 'L', 'marital_status' => 'MARRIED'],
            'members' => [
                ['name' => 'Anak Tanpa NIK', 'nik' => null, 'family_relationship' => 'CHILD'],
                ['name' => 'Anggota Tanpa NIK', 'nik' => null, 'family_relationship' => 'OTHER'],
            ],
        ];
    }

    private function region(): array
    {
        $rw = Rw::query()->create(['code' => '001', 'name' => 'RW 001']);
        $rt = Rt::query()->create(['rw_id' => $rw->id, 'code' => '001', 'name' => 'RT 001']);
        $otherRt = Rt::query()->create(['rw_id' => $rw->id, 'code' => '002', 'name' => 'RT 002']);
        $user = User::factory()->create(['role' => UserRole::RT, 'position' => null, 'rw_id' => $rw->id, 'rt_id' => $rt->id]);

        return [$user, $rt, $otherRt];
    }
}
