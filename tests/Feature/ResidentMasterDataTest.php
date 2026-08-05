<?php

namespace Tests\Feature;

use App\Enums\FamilyRelationship;
use App\Enums\UserRole;
use App\Enums\VillagePosition;
use App\Models\Citizen;
use App\Models\FamilyCard;
use App\Models\Report;
use App\Models\Rt;
use App\Models\Rw;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResidentMasterDataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_rt_only_lists_its_own_citizens_and_family_cards(): void
    {
        [$user, $ownRt, $otherRt] = $this->rtUserAndRegions();
        $ownCitizen = Citizen::factory()->for($ownRt)->create(['name' => 'Warga Milik RT']);
        $otherCitizen = Citizen::factory()->for($otherRt)->create(['name' => 'Warga RT Lain']);
        $ownCard = FamilyCard::factory()->for($ownRt)->create(['family_number' => '3201010101010101']);
        $otherCard = FamilyCard::factory()->for($otherRt)->create(['family_number' => '3201010101010102']);

        $this->actingAs($user)->get(route('rt.citizens.index'))->assertOk()->assertSee($ownCitizen->name)->assertDontSee($otherCitizen->name);
        $this->actingAs($user)->get(route('rt.family-cards.index'))->assertOk()->assertSee($ownCard->family_number)->assertDontSee($otherCard->family_number);
    }

    public function test_rt_gets_403_for_other_rt_records_and_request_rt_id_is_ignored(): void
    {
        [$user, $ownRt, $otherRt] = $this->rtUserAndRegions();
        $otherCitizen = Citizen::factory()->for($otherRt)->create();
        $otherCard = FamilyCard::factory()->for($otherRt)->create();
        $this->actingAs($user)->get(route('rt.citizens.edit', $otherCitizen))->assertForbidden();
        $this->actingAs($user)->put(route('rt.citizens.update', $otherCitizen), $this->citizenData())->assertForbidden();
        $this->actingAs($user)->get(route('rt.family-cards.edit', $otherCard))->assertForbidden();
        $this->actingAs($user)->patch(route('rt.family-cards.status.toggle', $otherCard))->assertForbidden();
        $this->actingAs($user)->post(route('rt.citizens.store'), [...$this->citizenData(), 'rt_id' => $otherRt->id])->assertRedirect();
        $this->assertDatabaseHas('citizens', ['name' => 'Warga Baru', 'rt_id' => $ownRt->id]);
    }

    public function test_rw_is_scoped_to_its_rw_and_is_read_only(): void
    {
        $firstRw = $this->rw('001');
        $secondRw = $this->rw('002');
        $firstRt = $this->rt($firstRw);
        $secondRt = $this->rt($secondRw);
        $user = User::factory()->create(['role' => UserRole::RW, 'position' => null, 'rw_id' => $firstRw->id, 'rt_id' => null]);
        Citizen::factory()->for($firstRt)->create(['name' => 'Dalam RW']);
        Citizen::factory()->for($secondRt)->create(['name' => 'Luar RW']);
        $this->actingAs($user)->get(route('rw.citizens.index'))->assertOk()->assertSee('Dalam RW')->assertDontSee('Luar RW')->assertDontSee('Tambah Warga');
        $this->actingAs($user)->post('/rw/citizens', $this->citizenData())->assertStatus(405);
    }

    public function test_village_permissions_allow_corrections_but_keep_head_read_only(): void
    {
        $rt = $this->rt($this->rw());
        $citizen = Citizen::factory()->for($rt)->create();
        foreach ([VillagePosition::SYSTEM_ADMIN, VillagePosition::VILLAGE_SECRETARY] as $position) {
            $user = $this->officer($position);
            $this->actingAs($user)->get(route('kelurahan.citizens.index'))->assertOk();
            $this->actingAs($user)->get(route('kelurahan.citizens.edit', $citizen))->assertOk();
        }
        $head = $this->officer(VillagePosition::VILLAGE_HEAD);
        $this->actingAs($head)->get(route('kelurahan.citizens.index'))->assertOk();
        $this->actingAs($head)->get(route('kelurahan.citizens.edit', $citizen))->assertForbidden();
        $this->actingAs($head)->patch(route('kelurahan.citizens.status.toggle', $citizen))->assertForbidden();
    }

    public function test_citizen_crud_and_active_toggle_work(): void
    {
        [$user, $rt] = $this->rtUserAndRegions();
        $this->actingAs($user)->post(route('rt.citizens.store'), $this->citizenData())->assertRedirect();
        $citizen = Citizen::query()->where('rt_id', $rt->id)->firstOrFail();
        $this->actingAs($user)->put(route('rt.citizens.update', $citizen), [...$this->citizenData(), 'name' => 'Nama Diperbarui'])->assertRedirect();
        $this->actingAs($user)->patch(route('rt.citizens.status.toggle', $citizen))->assertRedirect();
        $this->assertDatabaseHas('citizens', ['id' => $citizen->id, 'name' => 'Nama Diperbarui', 'is_active' => false, 'phone_normalized' => '6281234567890']);
    }

    public function test_family_card_crud_and_active_toggle_work(): void
    {
        [$user, $rt] = $this->rtUserAndRegions();
        $this->actingAs($user)->post(route('rt.family-cards.store'), ['family_number' => '3201010101010101', 'address' => 'Alamat'])->assertRedirect();
        $card = FamilyCard::query()->where('rt_id', $rt->id)->firstOrFail();
        $this->actingAs($user)->put(route('rt.family-cards.update', $card), ['family_number' => $card->family_number, 'address' => 'Alamat Baru'])->assertRedirect();
        $this->actingAs($user)->patch(route('rt.family-cards.status.toggle', $card))->assertRedirect();
        $this->assertDatabaseHas('family_cards', ['id' => $card->id, 'address' => 'Alamat Baru', 'is_active' => false]);
    }

    public function test_creating_family_card_redirects_to_detail(): void
    {
        [$user] = $this->rtUserAndRegions();

        $response = $this->actingAs($user)->post(route('rt.family-cards.store'), [
            'family_number' => '3201010101010101',
            'address' => 'Jalan Keluarga',
            'head_citizen_id' => 999,
        ]);

        $card = FamilyCard::query()->firstOrFail();
        $response->assertRedirect(route('rt.family-cards.show', $card));
        $this->assertNull($card->head_citizen_id);
    }

    public function test_rt_can_add_member_from_family_detail_and_context_ids_are_not_trusted(): void
    {
        [$user, $ownRt, $otherRt] = $this->rtUserAndRegions();
        $card = FamilyCard::factory()->for($ownRt)->create();
        $otherCard = FamilyCard::factory()->for($otherRt)->create();

        $this->actingAs($user)->post(route('rt.family-cards.members.store', $card), [
            ...$this->citizenData(),
            'phone' => null,
            'family_relationship' => FamilyRelationship::CHILD->value,
            'rt_id' => $otherRt->id,
            'family_card_id' => $otherCard->id,
        ])->assertRedirect(route('rt.family-cards.show', $card));

        $this->assertDatabaseHas('citizens', [
            'name' => 'Warga Baru',
            'rt_id' => $ownRt->id,
            'family_card_id' => $card->id,
            'family_relationship' => FamilyRelationship::CHILD->value,
            'phone' => null,
            'phone_normalized' => null,
        ]);
    }

    public function test_rt_cannot_add_member_to_another_rt_family_card(): void
    {
        [$user, , $otherRt] = $this->rtUserAndRegions();
        $otherCard = FamilyCard::factory()->for($otherRt)->create();

        $this->actingAs($user)->get(route('rt.family-cards.members.create', $otherCard))->assertForbidden();
        $this->actingAs($user)->post(route('rt.family-cards.members.store', $otherCard), $this->citizenData())->assertForbidden();
    }

    public function test_setting_head_synchronizes_card_and_single_head_relationship(): void
    {
        [$user, $rt] = $this->rtUserAndRegions();
        $card = FamilyCard::factory()->for($rt)->create();
        $oldHead = Citizen::factory()->for($rt)->create(['family_card_id' => $card->id, 'family_relationship' => FamilyRelationship::OTHER]);
        $newHead = Citizen::factory()->for($rt)->create(['family_card_id' => $card->id, 'family_relationship' => FamilyRelationship::SPOUSE]);

        $this->actingAs($user)->patch(route('rt.family-cards.head.update', [$card, $oldHead]))->assertRedirect();
        $this->actingAs($user)->patch(route('rt.family-cards.head.update', [$card, $newHead]))->assertRedirect();

        $this->assertSame($newHead->id, $card->fresh()->head_citizen_id);
        $this->assertSame(FamilyRelationship::OTHER, $oldHead->fresh()->family_relationship);
        $this->assertSame(FamilyRelationship::HEAD, $newHead->fresh()->family_relationship);
        $this->assertSame(1, Citizen::query()->where('family_card_id', $card->id)->where('family_relationship', FamilyRelationship::HEAD)->count());
    }

    public function test_head_must_be_member_of_the_same_family_card(): void
    {
        [$user, $rt] = $this->rtUserAndRegions();
        $card = FamilyCard::factory()->for($rt)->create();
        $otherCard = FamilyCard::factory()->for($rt)->create();
        $otherMember = Citizen::factory()->for($rt)->create(['family_card_id' => $otherCard->id]);

        $this->actingAs($user)->patch(route('rt.family-cards.head.update', [$card, $otherMember]))->assertStatus(422);
        $this->assertNull($card->fresh()->head_citizen_id);
    }

    public function test_family_and_citizen_details_show_membership_and_report_history(): void
    {
        [$user, $rt] = $this->rtUserAndRegions();
        $card = FamilyCard::factory()->for($rt)->create(['family_number' => '3201010101010101']);
        $citizen = Citizen::factory()->for($rt)->create(['name' => 'Anggota Detail', 'family_card_id' => $card->id, 'family_relationship' => FamilyRelationship::CHILD]);
        $report = Report::factory()->create(['citizen_id' => $citizen->id, 'rt_id' => $rt->id, 'title' => 'Riwayat Warga']);

        $memberUrl = route('rt.citizens.show', $citizen);
        $this->actingAs($user)->get(route('rt.family-cards.show', $card))->assertOk()
            ->assertSee('Informasi Kartu Keluarga')
            ->assertSee('Daftar Anggota Keluarga')
            ->assertSee('Informasi Kelengkapan Data')
            ->assertSee('Aksi Administratif')
            ->assertSee('Anggota Detail')
            ->assertSee('Jumlah Anggota')
            ->assertSee('1 orang')
            ->assertSee('data-row-url="'.$memberUrl.'"', false)
            ->assertSee('href="'.$memberUrl.'"', false)
            ->assertDontSee('onclick=', false);
        $this->actingAs($user)->get(route('rt.citizens.show', $citizen))->assertOk()
            ->assertSee('Identitas')
            ->assertSee('Keluarga')
            ->assertSee('Domisili')
            ->assertSee('Riwayat Layanan')
            ->assertSee($card->family_number)
            ->assertSee(FamilyRelationship::CHILD->label())
            ->assertSee('RW '.$rt->rw->code.' / RT '.$rt->code)
            ->assertSee($report->ticket_number)
            ->assertSee($report->title);
    }

    public function test_family_detail_highlights_head_and_calculates_completeness_from_loaded_members(): void
    {
        [$user, $rt] = $this->rtUserAndRegions();
        $card = FamilyCard::factory()->for($rt)->create(['address' => 'Jalan Administrasi Desa']);
        $head = Citizen::factory()->for($rt)->create(['name' => 'Kepala Visual', 'family_card_id' => $card->id, 'nik' => '3201010101010101', 'phone' => '081211111111', 'phone_normalized' => '6281211111111']);
        Citizen::factory()->for($rt)->create(['family_card_id' => $card->id, 'nik' => null, 'phone' => null, 'phone_normalized' => null]);
        Citizen::factory()->for($rt)->create(['family_card_id' => $card->id, 'is_active' => false]);
        $this->actingAs($user)->patch(route('rt.family-cards.head.update', [$card, $head]))->assertRedirect();

        $this->actingAs($user)->get(route('rt.family-cards.show', $card))->assertOk()
            ->assertSee('Kepala Visual')
            ->assertSee('table-primary', false)
            ->assertSee('Kepala Keluarga')
            ->assertSee('Anggota tanpa NIK')
            ->assertSee('Anggota tanpa nomor telepon')
            ->assertSee('Anggota nonaktif')
            ->assertSee('Total anggota aktif')
            ->assertSee('<strong class="fs-4 d-block">0</strong><span class="small text-secondary">Kepala keluarga belum ditentukan</span>', false)
            ->assertSee('<strong class="fs-4 d-block">2</strong><span class="small text-secondary">Anggota tanpa NIK</span>', false)
            ->assertSee('<strong class="fs-4 d-block">1</strong><span class="small text-secondary">Anggota tanpa nomor telepon</span>', false)
            ->assertSee('<strong class="fs-4 d-block">1</strong><span class="small text-secondary">Anggota nonaktif</span>', false)
            ->assertSee('<strong class="fs-4 d-block">2</strong><span class="small text-secondary">Total anggota aktif</span>', false)
            ->assertSee('Jalan Administrasi Desa');
    }

    public function test_empty_family_detail_explains_how_to_add_first_member(): void
    {
        [$user, $rt] = $this->rtUserAndRegions();
        $card = FamilyCard::factory()->for($rt)->create();

        $this->actingAs($user)->get(route('rt.family-cards.show', $card))->assertOk()
            ->assertSee('KK belum memiliki anggota')
            ->assertSee('Gunakan tombol “Tambah Anggota”')
            ->assertSee(route('rt.family-cards.members.create', $card));
    }

    public function test_member_form_from_family_card_shows_context_without_family_or_region_selectors(): void
    {
        [$user, $rt] = $this->rtUserAndRegions();
        $card = FamilyCard::factory()->for($rt)->create(['family_number' => '3201010101010101']);

        $this->actingAs($user)->get(route('rt.family-cards.members.create', $card))->assertOk()
            ->assertSee('KK '.$card->family_number)
            ->assertSee('RW '.$rt->rw->code.' / RT '.$rt->code)
            ->assertSee('Simpan Anggota')
            ->assertSee('NIK harus terdiri dari 16 digit jika diisi.')
            ->assertSee('Nomor telepon bersifat opsional.')
            ->assertDontSee('name="family_card_id"', false)
            ->assertDontSee('name="region_rt_id"', false)
            ->assertDontSee('name="rt_id"', false)
            ->assertDontSee('name="rw_id"', false);
    }

    public function test_family_list_renders_completeness_badges_and_citizen_list_relationship(): void
    {
        [$user, $rt] = $this->rtUserAndRegions();
        $withoutHead = FamilyCard::factory()->for($rt)->create(['family_number' => '3201010101010101']);
        $withMissingNik = FamilyCard::factory()->for($rt)->create(['family_number' => '3201010101010102']);
        $head = Citizen::factory()->for($rt)->create(['family_card_id' => $withMissingNik->id, 'nik' => null, 'family_relationship' => FamilyRelationship::CHILD]);
        $this->actingAs($user)->patch(route('rt.family-cards.head.update', [$withMissingNik, $head]))->assertRedirect();

        $this->actingAs($user)->get(route('rt.family-cards.index'))->assertOk()
            ->assertSee($withoutHead->family_number)
            ->assertSee('Belum ada kepala keluarga')
            ->assertSee($withMissingNik->family_number)
            ->assertSee('Ada anggota tanpa NIK')
            ->assertDontSee('onclick=', false);
        $this->actingAs($user)->get(route('rt.citizens.index'))->assertOk()
            ->assertSee(FamilyRelationship::HEAD->label())
            ->assertSee('Detail');
    }

    public function test_rw_and_village_head_details_are_read_only_while_authorized_officers_can_mutate(): void
    {
        $rw = $this->rw();
        $rt = $this->rt($rw);
        $card = FamilyCard::factory()->for($rt)->create();
        $citizen = Citizen::factory()->for($rt)->create(['family_card_id' => $card->id]);
        $rwUser = User::factory()->create(['role' => UserRole::RW, 'position' => null, 'rw_id' => $rw->id, 'rt_id' => null]);
        $head = $this->officer(VillagePosition::VILLAGE_HEAD);

        foreach ([[$rwUser, 'rw'], [$head, 'kelurahan']] as [$actor, $prefix]) {
            $this->actingAs($actor)->get(route($prefix.'.family-cards.show', $card))->assertOk()->assertDontSee('Tambah Anggota')->assertDontSee('Edit KK')->assertDontSee('Jadikan Kepala Keluarga');
            $this->actingAs($actor)->get(route($prefix.'.citizens.show', $citizen))->assertOk()->assertDontSee('Edit Warga');
        }

        foreach ([VillagePosition::SYSTEM_ADMIN, VillagePosition::VILLAGE_SECRETARY] as $position) {
            $actor = $this->officer($position);
            $this->actingAs($actor)->get(route('kelurahan.family-cards.show', $card))->assertOk()->assertSee('Tambah Anggota')->assertSee('Edit KK');
        }
    }

    public function test_detail_pages_use_eager_loading(): void
    {
        [$user, $rt] = $this->rtUserAndRegions();
        $card = FamilyCard::factory()->for($rt)->create();
        $citizen = Citizen::factory()->for($rt)->create(['family_card_id' => $card->id]);
        Report::factory()->create(['citizen_id' => $citizen->id, 'rt_id' => $rt->id]);

        Model::preventLazyLoading();
        try {
            $this->actingAs($user)->get(route('rt.family-cards.show', $card))->assertOk();
            $this->actingAs($user)->get(route('rt.citizens.show', $citizen))->assertOk();
        } finally {
            Model::preventLazyLoading(false);
        }
    }

    public function test_duplicate_nik_and_family_number_are_rejected(): void
    {
        [$user, $rt] = $this->rtUserAndRegions();
        Citizen::factory()->for($rt)->create(['nik' => '3201010101010101']);
        FamilyCard::factory()->for($rt)->create(['family_number' => '3201010101010102']);
        $this->actingAs($user)->from(route('rt.citizens.create'))->post(route('rt.citizens.store'), [...$this->citizenData(), 'nik' => '3201010101010101'])->assertSessionHasErrors('nik');
        $this->actingAs($user)->from(route('rt.family-cards.create'))->post(route('rt.family-cards.store'), ['family_number' => '3201010101010102'])->assertSessionHasErrors('family_number');
    }

    public function test_cross_rt_family_assignment_is_rejected_and_legacy_citizen_without_card_is_valid(): void
    {
        [$user, $ownRt, $otherRt] = $this->rtUserAndRegions();
        $otherCard = FamilyCard::factory()->for($otherRt)->create();
        $this->actingAs($user)->post(route('rt.citizens.store'), [...$this->citizenData(), 'family_card_id' => $otherCard->id])->assertSessionHasErrors('family_card_id');
        $legacy = Citizen::query()->create(['rt_id' => $ownRt->id, 'name' => 'Warga Lama', 'phone' => '081299999999', 'phone_normalized' => '6281299999999']);
        $this->assertNull($legacy->family_card_id);
        $this->assertTrue($legacy->fresh()->is_active);
    }

    public function test_indexes_render_without_lazy_loading(): void
    {
        [$user, $rt] = $this->rtUserAndRegions();
        $citizen = Citizen::factory()->for($rt)->create();
        FamilyCard::factory()->for($rt)->create();
        Model::preventLazyLoading();
        try {
            $this->actingAs($user)->get(route('rt.citizens.index'))->assertOk();
            $this->actingAs($user)->get(route('rt.family-cards.index'))->assertOk();
        } finally {
            Model::preventLazyLoading(false);
        }
    }

    public function test_dashboards_show_correct_active_resident_and_card_totals(): void
    {
        [$rtUser, $rt] = $this->rtUserAndRegions();
        Citizen::factory()->for($rt)->create();
        Citizen::factory()->for($rt)->create(['is_active' => false]);
        FamilyCard::factory()->for($rt)->create();
        FamilyCard::factory()->for($rt)->create(['is_active' => false]);
        $this->actingAs($rtUser)->get(route('rt.dashboard'))
            ->assertViewHas('activeCitizenCount', 1)
            ->assertViewHas('activeFamilyCardCount', 1)
            ->assertViewHas('familyCardsWithoutHeadCount', 2)
            ->assertViewHas('citizensWithoutFamilyCardCount', 2)
            ->assertViewHas('citizensWithoutNikCount', 2);
        $rwUser = User::factory()->create(['role' => UserRole::RW, 'position' => null, 'rw_id' => $rt->rw_id, 'rt_id' => null]);
        $this->actingAs($rwUser)->get(route('rw.dashboard'))->assertViewHas('activeCitizenCount', 1)->assertViewHas('activeFamilyCardCount', 1);
        $this->actingAs($this->officer(VillagePosition::VILLAGE_SECRETARY))->get(route('kelurahan.dashboard'))->assertViewHas('activeCitizenCount', 1)->assertViewHas('activeFamilyCardCount', 1);
    }

    private function citizenData(): array
    {
        return ['name' => 'Warga Baru', 'phone' => '0812-3456-7890', 'nik' => null, 'family_card_id' => null, 'gender' => null, 'birth_place' => null, 'birth_date' => null, 'address' => null];
    }

    private function rw(string $code = '001'): Rw
    {
        return Rw::query()->create(['code' => $code, 'name' => "RW {$code}"]);
    }

    private function rt(Rw $rw, string $code = '001'): Rt
    {
        return Rt::query()->create(['rw_id' => $rw->id, 'code' => $code, 'name' => "RT {$code}"]);
    }

    private function officer(VillagePosition $position): User
    {
        return User::factory()->create(['role' => $position === VillagePosition::SYSTEM_ADMIN ? UserRole::ADMIN : UserRole::KELURAHAN, 'position' => $position, 'rw_id' => null, 'rt_id' => null]);
    }

    private function rtUserAndRegions(): array
    {
        $rw = $this->rw();
        $own = $this->rt($rw, '001');
        $other = $this->rt($rw, '002');
        $user = User::factory()->create(['role' => UserRole::RT, 'position' => null, 'rw_id' => $rw->id, 'rt_id' => $own->id]);

        return [$user, $own, $other];
    }
}
