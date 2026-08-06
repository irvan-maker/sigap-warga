<?php

namespace Tests\Feature;

use App\Enums\LetterStatus;
use App\Enums\LetterType;
use App\Enums\UserRole;
use App\Enums\VillagePosition;
use App\Models\Citizen;
use App\Models\FamilyCard;
use App\Models\Rt;
use App\Models\Rw;
use App\Models\User;
use App\Models\VillageLetter;
use App\Services\VillageLetterWorkflow;
use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VillageLetterAdministrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_rt_can_only_create_for_own_citizen_and_request_rt_is_ignored(): void
    {
        [$rtUser,$ownRt,$otherRt] = $this->regions();
        $own = Citizen::factory()->for($ownRt)->create();
        $other = Citizen::factory()->for($otherRt)->create();
        $this->actingAs($rtUser)->post(route('rt.letters.store'), $this->payload($other))->assertNotFound();
        $this->actingAs($rtUser)->post(route('rt.letters.store'), [...$this->payload($own), 'rt_id' => $otherRt->id])->assertRedirect();
        $this->assertDatabaseHas('village_letters', ['citizen_id' => $own->id, 'rt_id' => $ownRt->id, 'status' => 'DRAFT']);
    }

    public function test_rw_scope_and_cross_region_url_are_enforced(): void
    {
        [$rtUser,$ownRt,$otherRt] = $this->regions();
        $letter = $this->letter(Citizen::factory()->for($ownRt)->create(), $rtUser, LetterStatus::SUBMITTED);
        $otherRw = $otherRt->rw;
        $rwUser = $this->rwUser($otherRw);
        $this->actingAs($rwUser)->get(route('rw.letters.index'))->assertDontSee($letter->purpose);
        $this->actingAs($rwUser)->get(route('rw.letters.show', $letter))->assertForbidden();
    }

    public function test_village_officers_see_all_but_head_is_read_only(): void
    {
        [$rtUser,$rt] = $this->regions();
        $letter = $this->letter(Citizen::factory()->for($rt)->create(), $rtUser, LetterStatus::RW_REVIEWED);
        foreach ([VillagePosition::SYSTEM_ADMIN, VillagePosition::VILLAGE_SECRETARY] as $p) {
            $this->actingAs($this->officer($p))->get(route('kelurahan.letters.show', $letter))->assertOk()->assertSee('Setujui Surat');
        } $head = $this->officer(VillagePosition::VILLAGE_HEAD);
        $this->actingAs($head)->get(route('kelurahan.letters.show', $letter))->assertOk()->assertDontSee('Setujui Surat')->assertDontSee('Tolak');
        $this->actingAs($head)->patch(route('kelurahan.letters.approve', $letter))->assertForbidden();
    }

    public function test_workflow_validates_transitions_and_rejection_reason(): void
    {
        [$rtUser,$rt] = $this->regions();
        $letter = $this->letter(Citizen::factory()->for($rt)->create(), $rtUser);
        $flow = app(VillageLetterWorkflow::class);
        $flow->transition($letter, LetterStatus::SUBMITTED, $rtUser);
        $rw = $this->rwUser($rt->rw);
        $flow->transition($letter, LetterStatus::RW_REVIEWED, $rw);
        $secretary = $this->officer(VillagePosition::VILLAGE_SECRETARY);
        $flow->transition($letter, LetterStatus::APPROVED, $secretary);
        $this->assertSame(LetterStatus::APPROVED, $letter->fresh()->status);
        $this->expectException(DomainException::class);
        $flow->transition($letter, LetterStatus::SUBMITTED, $secretary);
    }

    public function test_rejection_requires_reason(): void
    {
        [$rtUser,$rt] = $this->regions();
        $letter = $this->letter(Citizen::factory()->for($rt)->create(), $rtUser, LetterStatus::SUBMITTED);
        $this->actingAs($this->rwUser($rt->rw))->patch(route('rw.letters.reject', $letter), ['note' => ''])->assertSessionHasErrors('note');
        $this->assertSame(LetterStatus::SUBMITTED, $letter->fresh()->status);
    }

    public function test_number_is_only_created_when_issued_and_is_unique(): void
    {
        [$rtUser,$rt] = $this->regions();
        $secretary = $this->officer(VillagePosition::VILLAGE_SECRETARY);
        $flow = app(VillageLetterWorkflow::class);
        $letters = [];
        foreach (range(1, 2) as $i) {
            $letter = $this->letter(Citizen::factory()->for($rt)->create(), $rtUser, LetterStatus::APPROVED);
            $this->assertNull($letter->letter_number);
            $flow->transition($letter, LetterStatus::ISSUED, $secretary);
            $letters[] = $letter->fresh();
        } $this->assertNotSame($letters[0]->letter_number, $letters[1]->letter_number);
        $this->assertMatchesRegularExpression('/^001\/SP-UM\/CS\/[IVX]+\/\d{4}$/', $letters[0]->letter_number);
        $this->assertDatabaseCount('letter_number_sequences', 1);
    }

    public function test_pdf_only_for_issued_and_renders_master_data(): void
    {
        [$rtUser,$rt] = $this->regions();
        $card = FamilyCard::factory()->for($rt)->create(['family_number' => '3201010101010101', 'address' => 'Jalan PDF']);
        $citizen = Citizen::factory()->for($rt)->create(['name' => 'Warga PDF', 'nik' => '3201010101010102', 'family_card_id' => $card->id]);
        $letter = $this->letter($citizen, $rtUser);
        $this->actingAs($rtUser)->get(route('rt.letters.pdf', $letter))->assertForbidden();
        $letter->update(['status' => LetterStatus::ISSUED, 'letter_number' => '001/SP-UM/CS/VIII/2026', 'issued_at' => now()]);
        $this->actingAs($rtUser)->get(route('rt.letters.pdf', $letter))->assertOk()->assertHeader('Content-Type', 'application/pdf')->assertSee('%PDF', false);
        $this->actingAs($rtUser)->get(route('rt.letters.show', $letter))->assertSee('Warga PDF')->assertSee('3201010101010101')->assertSee('Jalan PDF');
    }

    public function test_search_filter_pagination_and_eager_loading_work(): void
    {
        [$rtUser,$rt] = $this->regions();
        foreach (range(1, 16) as $i) {
            $this->letter(Citizen::factory()->for($rt)->create(['name' => "Warga Surat {$i}"]), $rtUser, $i === 1 ? LetterStatus::SUBMITTED : LetterStatus::DRAFT);
        } $this->actingAs($rtUser)->get(route('rt.letters.index', ['search' => 'Warga Surat 1', 'status' => 'SUBMITTED']))->assertOk()->assertSee('Warga Surat 1')->assertDontSee('Warga Surat 2');
        $this->actingAs($rtUser)->get(route('rt.letters.index'))->assertViewHas('letters', fn ($p) => $p->total() === 16 && $p->count() === 15);
        Model::preventLazyLoading();
        try {
            $this->actingAs($rtUser)->get(route('rt.letters.index'))->assertOk();
        } finally {
            Model::preventLazyLoading(false);
        }
    }

    public function test_dashboards_render_letter_cards(): void
    {
        [$rtUser,$rt] = $this->regions();
        $this->actingAs($rtUser)->get(route('rt.dashboard'))->assertSee('Pengajuan Surat');
        $this->actingAs($this->rwUser($rt->rw))->get(route('rw.dashboard'))->assertSee('Verifikasi Surat');
        $this->actingAs($this->officer(VillagePosition::VILLAGE_SECRETARY))->get(route('kelurahan.dashboard'))->assertSee('Administrasi Surat');
    }

    private function payload(Citizen $c): array
    {
        return ['citizen_id' => $c->id, 'letter_type' => LetterType::GENERAL_INTRODUCTION->value, 'purpose' => 'Keperluan administrasi', 'notes' => null];
    }

    private function letter(Citizen $c, User $u, LetterStatus $s = LetterStatus::DRAFT): VillageLetter
    {
        return VillageLetter::query()->create(['letter_type' => LetterType::GENERAL_INTRODUCTION, 'citizen_id' => $c->id, 'rt_id' => $c->rt_id, 'submitted_by' => $u->id, 'purpose' => 'Keperluan surat unik', 'status' => $s, 'submitted_at' => $s === LetterStatus::DRAFT ? null : now()]);
    }

    private function regions(): array
    {
        $rw = Rw::query()->create(['code' => '001', 'name' => 'RW 001']);
        $own = Rt::query()->create(['rw_id' => $rw->id, 'code' => '001', 'name' => 'RT 001']);
        $otherRw = Rw::query()->create(['code' => '002', 'name' => 'RW 002']);
        $other = Rt::query()->create(['rw_id' => $otherRw->id, 'code' => '001', 'name' => 'RT 001']);
        $user = User::factory()->create(['role' => UserRole::RT, 'position' => null, 'rw_id' => $rw->id, 'rt_id' => $own->id]);

        return [$user, $own, $other];
    }

    private function rwUser(Rw $rw): User
    {
        return User::factory()->create(['role' => UserRole::RW, 'position' => null, 'rw_id' => $rw->id, 'rt_id' => null]);
    }

    private function officer(VillagePosition $p): User
    {
        return User::factory()->create(['role' => $p === VillagePosition::SYSTEM_ADMIN ? UserRole::ADMIN : UserRole::KELURAHAN, 'position' => $p, 'rw_id' => null, 'rt_id' => null]);
    }
}
