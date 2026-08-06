<?php

namespace Tests\Feature;

use App\Enums\LetterStatus;
use App\Enums\LetterType;
use App\Enums\UserRole;
use App\Models\Citizen;
use App\Models\Rt;
use App\Models\Rw;
use App\Models\User;
use App\Models\VillageLetter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class PublicPortalAndLetterTrackingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_root_is_a_public_portal_for_guests_and_authenticated_users(): void
    {
        $this->get('/')->assertOk()->assertSee(config('village.name'))->assertSee('Lacak Surat');
        $this->actingAs(User::factory()->create())->get('/')->assertOk()->assertSee('Masuk Dashboard');
    }

    public function test_valid_letter_tracking_requires_reference_and_phone(): void
    {
        $letter = $this->letter();
        $this->post(route('letter-tracking.store'), ['reference' => $letter->public_tracking_code])->assertSessionHasErrors('phone');
        $this->post(route('letter-tracking.store'), ['reference' => $letter->public_tracking_code, 'phone' => '081200000000'])
            ->assertOk()->assertSee('Data belum dapat ditemukan.')->assertDontSee($letter->letter_type->label());
        $this->post(route('letter-tracking.store'), ['reference' => $letter->public_tracking_code, 'phone' => '081234567890'])
            ->assertOk()->assertSee($letter->public_tracking_code)->assertSee($letter->letter_type->label())->assertDontSee('Download PDF');
    }

    public function test_issued_letter_has_only_a_temporary_signed_download(): void
    {
        $letter = $this->letter(LetterStatus::ISSUED);
        $response = $this->post(route('letter-tracking.store'), ['reference' => $letter->letter_number, 'phone' => '081234567890']);
        $response->assertOk()->assertSee('Download PDF')->assertDontSee($letter->citizen->nik ?? 'never-visible');
        $this->get(route('letter-tracking.download', ['trackingCode' => $letter->public_tracking_code]))->assertForbidden();
        $expired = URL::temporarySignedRoute('letter-tracking.download', now()->subMinute(), ['trackingCode' => $letter->public_tracking_code]);
        $this->get($expired)->assertForbidden();
    }

    public function test_letter_tracking_is_rate_limited(): void
    {
        $payload = ['reference' => 'SRT-DOESNOTEXIST', 'phone' => '081200000000'];
        foreach (range(1, 5) as $attempt) {
            $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.44'])->post(route('letter-tracking.store'), $payload)->assertOk();
        }
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.44'])->post(route('letter-tracking.store'), $payload)->assertTooManyRequests();
    }

    private function letter(LetterStatus $status = LetterStatus::DRAFT): VillageLetter
    {
        $rw = Rw::query()->create(['code' => '001', 'name' => 'RW 001']);
        $rt = Rt::query()->create(['rw_id' => $rw->id, 'code' => '001', 'name' => 'RT 001']);
        $user = User::factory()->create(['role' => UserRole::RT, 'rw_id' => $rw->id, 'rt_id' => $rt->id]);
        $citizen = Citizen::factory()->for($rt)->create(['phone' => '081234567890', 'phone_normalized' => '6281234567890']);
        $letter = VillageLetter::query()->create([
            'letter_number' => $status === LetterStatus::ISSUED ? '001/SP-UM/CS/VIII/2026' : null,
            'letter_type' => LetterType::GENERAL_INTRODUCTION,
            'citizen_id' => $citizen->id,
            'rt_id' => $rt->id,
            'submitted_by' => $user->id,
            'purpose' => 'Keperluan administrasi',
            'notes' => 'CATATAN INTERNAL RAHASIA',
            'status' => $status,
            'issued_at' => $status === LetterStatus::ISSUED ? now() : null,
        ]);
        $letter->histories()->create(['new_status' => $status, 'note' => 'CATATAN PETUGAS RAHASIA']);

        return $letter->fresh(['citizen']);
    }
}
