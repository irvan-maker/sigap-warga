<?php

namespace Tests\Feature;

use App\Enums\ReportStatus;
use App\Models\Citizen;
use App\Models\Report;
use App\Models\Rt;
use App\Models\Rw;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManualReportCreationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_guest_cannot_access_report_creation(): void
    {
        $this->get(route('reports.create'))->assertRedirect(route('login'));
    }

    public function test_authenticated_admin_can_access_form(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->get(route('reports.create'))
            ->assertOk()
            ->assertSee('Buat Laporan');
    }

    public function test_valid_report_can_be_created(): void
    {
        $rt = $this->createRt();

        $response = $this->actingAs(User::factory()->create())
            ->post(route('reports.store'), $this->validPayload($rt));

        $report = Report::query()->sole();
        $response->assertRedirect(route('reports.show', $report));
        $this->assertSame(ReportStatus::NEW, $report->status);
        $this->assertNotNull($report->reported_at);
        $this->assertNull($report->inbound_request_id);
    }

    public function test_new_citizen_is_created_when_phone_is_unknown(): void
    {
        $rt = $this->createRt();

        $this->actingAs(User::factory()->create())
            ->post(route('reports.store'), $this->validPayload($rt));

        $this->assertDatabaseHas('citizens', [
            'rt_id' => $rt->id,
            'name' => 'Warga Manual',
            'phone_normalized' => '6281234567890',
            'family_card_id' => null,
            'family_relationship' => null,
            'nik' => null,
        ]);
    }

    public function test_existing_citizen_is_reused(): void
    {
        $rt = $this->createRt();
        $citizen = Citizen::factory()->for($rt)->create([
            'name' => 'Nama Lama',
            'phone_normalized' => '6281234567890',
        ]);

        $this->actingAs(User::factory()->create())
            ->post(route('reports.store'), $this->validPayload($rt));

        $this->assertDatabaseCount('citizens', 1);
        $this->assertSame($citizen->id, Report::query()->sole()->citizen_id);
        $this->assertSame('Warga Manual', $citizen->fresh()->name);
    }

    public function test_phone_is_normalized(): void
    {
        $rt = $this->createRt();
        $payload = $this->validPayload($rt);
        $payload['phone'] = '+62 812-3456-7890';

        $this->actingAs(User::factory()->create())
            ->post(route('reports.store'), $payload);

        $this->assertDatabaseHas('citizens', ['phone_normalized' => '6281234567890']);
    }

    public function test_citizen_from_another_rt_is_rejected(): void
    {
        $rw = $this->createRw();
        $firstRt = $this->createRt($rw, '001');
        $secondRt = $this->createRt($rw, '002');
        Citizen::factory()->for($firstRt)->create([
            'phone_normalized' => '6281234567890',
        ]);

        $this->actingAs(User::factory()->create())
            ->from(route('reports.create'))
            ->post(route('reports.store'), $this->validPayload($secondRt))
            ->assertRedirect(route('reports.create'))
            ->assertSessionHasErrors('phone');

        $this->assertDatabaseCount('reports', 0);
    }

    public function test_ticket_number_is_generated(): void
    {
        $rt = $this->createRt();

        $this->actingAs(User::factory()->create())
            ->post(route('reports.store'), $this->validPayload($rt));

        $this->assertMatchesRegularExpression(
            '/^SGW-'.now()->format('Y').'-\d{5}$/',
            Report::query()->sole()->ticket_number,
        );
    }

    public function test_initial_new_history_exists(): void
    {
        $rt = $this->createRt();

        $this->actingAs(User::factory()->create())
            ->post(route('reports.store'), $this->validPayload($rt));

        $report = Report::query()->sole();
        $this->assertDatabaseHas('report_histories', [
            'report_id' => $report->id,
            'old_status' => null,
            'new_status' => ReportStatus::NEW->value,
        ]);
    }

    public function test_report_detail_page_is_protected(): void
    {
        $report = $this->createReport();

        $this->get(route('reports.show', $report))->assertRedirect(route('login'));
    }

    public function test_report_detail_displays_expected_information(): void
    {
        $report = $this->createReport();

        $this->actingAs(User::factory()->create())
            ->get(route('reports.show', $report))
            ->assertOk()
            ->assertSee($report->ticket_number)
            ->assertSee($report->citizen->name)
            ->assertSee($report->rt->code)
            ->assertSee($report->rt->name)
            ->assertSee($report->title)
            ->assertSee($report->description)
            ->assertSee(ReportStatus::NEW->value)
            ->assertSee('Riwayat status');
    }

    private function createReport(): Report
    {
        $rt = $this->createRt();
        $citizen = Citizen::factory()->for($rt)->create();

        return Report::factory()->create([
            'citizen_id' => $citizen->id,
            'rt_id' => $rt->id,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(Rt $rt): array
    {
        return [
            'rt_id' => $rt->id,
            'citizen_name' => 'Warga Manual',
            'phone' => '0812 3456 7890',
            'title' => 'Jalan lingkungan rusak',
            'description' => 'Permukaan jalan rusak dan perlu diperbaiki.',
        ];
    }

    private function createRw(string $code = '001'): Rw
    {
        return Rw::query()->create(['code' => $code, 'name' => "RW {$code}"]);
    }

    private function createRt(?Rw $rw = null, string $code = '001'): Rt
    {
        $rw ??= $this->createRw();

        return Rt::query()->create([
            'rw_id' => $rw->id,
            'code' => $code,
            'name' => "RT {$code}",
        ]);
    }
}
