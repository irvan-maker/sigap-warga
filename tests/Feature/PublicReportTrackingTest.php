<?php

namespace Tests\Feature;

use App\Enums\ReportStatus;
use App\Models\Citizen;
use App\Models\Report;
use App\Models\Rt;
use App\Models\Rw;
use App\Models\User;
use App\Services\ReportStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicReportTrackingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_tracking_page_is_publicly_accessible(): void
    {
        $this->get(route('tracking.index'))
            ->assertOk()
            ->assertSee('Lacak Laporan');
    }

    public function test_valid_ticket_and_phone_show_report(): void
    {
        [$report, $citizen] = $this->createReport();

        $this->post(route('tracking.store'), [
            'ticket_number' => $report->ticket_number,
            'phone' => '0812 3456 7890',
        ])->assertOk()
            ->assertSee($report->ticket_number)
            ->assertSee($report->title)
            ->assertDontSee($citizen->name)
            ->assertDontSee($report->description);
    }

    public function test_wrong_phone_is_rejected(): void
    {
        [$report] = $this->createReport();

        $this->post(route('tracking.store'), [
            'ticket_number' => $report->ticket_number,
            'phone' => '0812 0000 0000',
        ])->assertOk()->assertSee('Data belum dapat ditemukan.');
    }

    public function test_wrong_ticket_is_rejected(): void
    {
        $this->createReport();

        $this->post(route('tracking.store'), [
            'ticket_number' => 'SGW-2026-99999',
            'phone' => '0812 3456 7890',
        ])->assertOk()->assertSee('Data belum dapat ditemukan.');
    }

    public function test_not_found_response_is_generic(): void
    {
        $response = $this->post(route('tracking.store'), [
            'ticket_number' => 'SGW-2026-99999',
            'phone' => '0812 0000 0000',
        ]);

        $response->assertOk()
            ->assertSee('Data belum dapat ditemukan.')
            ->assertDontSee('tiket salah')
            ->assertDontSee('nomor telepon salah');
    }

    public function test_history_is_displayed_in_chronological_order(): void
    {
        [$report] = $this->createReport();
        $service = app(ReportStatusService::class);
        $report = $service->transition(
            $report,
            ReportStatus::PROCESSING,
            note: 'Tahap pertama yang unik.',
        );
        $service->transition(
            $report,
            ReportStatus::COMPLETED,
            note: 'Tahap kedua yang unik.',
        );

        $this->post(route('tracking.store'), [
            'ticket_number' => $report->ticket_number,
            'phone' => '081234567890',
        ])->assertOk()->assertSeeInOrder([
            'Tahap pertama yang unik.',
            'Tahap kedua yang unik.',
        ]);
    }

    public function test_sensitive_fields_are_not_exposed(): void
    {
        [$report, $citizen] = $this->createReport();
        $actor = User::factory()->create(['email' => 'private-staff@example.test']);
        app(ReportStatusService::class)->transition(
            $report,
            ReportStatus::PROCESSING,
            $actor,
        );

        $this->post(route('tracking.store'), [
            'ticket_number' => $report->ticket_number,
            'phone' => '081234567890',
        ])->assertOk()
            ->assertDontSee($citizen->phone)
            ->assertDontSee($citizen->phone_normalized)
            ->assertDontSee($actor->email);
    }

    public function test_tracking_attempts_are_rate_limited(): void
    {
        $payload = [
            'ticket_number' => 'SGW-2026-99999',
            'phone' => '081200000000',
        ];

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
                ->post(route('tracking.store'), $payload)
                ->assertOk();
        }

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
            ->post(route('tracking.store'), $payload)
            ->assertTooManyRequests();
    }

    /**
     * @return array{Report, Citizen}
     */
    private function createReport(): array
    {
        $rw = Rw::query()->create(['code' => '001', 'name' => 'RW 001']);
        $rt = Rt::query()->create(['rw_id' => $rw->id, 'code' => '001', 'name' => 'RT 001']);
        $citizen = Citizen::factory()->for($rt)->create([
            'phone' => '081234567890',
            'phone_normalized' => '6281234567890',
        ]);
        $report = Report::factory()->create([
            'citizen_id' => $citizen->id,
            'rt_id' => $rt->id,
        ]);

        return [$report, $citizen];
    }
}
