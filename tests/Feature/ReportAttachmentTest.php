<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Citizen;
use App\Models\Report;
use App\Models\ReportAttachment;
use App\Models\Rt;
use App\Models\Rw;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReportAttachmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->withoutVite();
    }

    public function test_report_can_be_created_without_photo(): void
    {
        $this->submitManualReport();

        $this->assertDatabaseCount('reports', 1);
        $this->assertDatabaseCount('report_attachments', 0);
    }

    public function test_one_valid_photo_can_be_uploaded(): void
    {
        $this->submitManualReport([
            'photos' => [UploadedFile::fake()->image('jalan-rusak.jpg')],
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseCount('report_attachments', 1);
    }

    public function test_multiple_valid_photos_can_be_uploaded(): void
    {
        $this->submitManualReport([
            'photos' => [
                UploadedFile::fake()->image('foto-1.jpg'),
                UploadedFile::fake()->image('foto-2.png'),
                UploadedFile::fake()->image('foto-3.jpg'),
            ],
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseCount('report_attachments', 3);
    }

    public function test_invalid_mime_type_is_rejected(): void
    {
        $this->submitManualReport([
            'photos' => [UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf')],
        ])->assertSessionHasErrors('photos.0');

        $this->assertDatabaseCount('reports', 0);
    }

    public function test_oversized_photo_is_rejected(): void
    {
        $this->submitManualReport([
            'photos' => [UploadedFile::fake()->image('besar.jpg')->size(5121)],
        ])->assertSessionHasErrors('photos.0');

        $this->assertDatabaseCount('reports', 0);
    }

    public function test_more_than_three_photos_are_rejected(): void
    {
        $this->submitManualReport([
            'photos' => collect(range(1, 4))
                ->map(fn (int $number): UploadedFile => UploadedFile::fake()->image("foto-{$number}.jpg"))
                ->all(),
        ])->assertSessionHasErrors('photos');

        $this->assertDatabaseCount('reports', 0);
    }

    public function test_attachment_metadata_is_stored_correctly(): void
    {
        $photo = UploadedFile::fake()->image('bukti-asli.jpg')->size(256);
        $this->submitManualReport(['photos' => [$photo]]);
        $attachment = ReportAttachment::query()->sole();

        $this->assertSame('bukti-asli.jpg', $attachment->original_name);
        $this->assertNotSame($attachment->original_name, $attachment->stored_name);
        $this->assertMatchesRegularExpression('/^[0-9a-f-]{36}\.jpg$/', $attachment->stored_name);
        $this->assertSame("reports/{$attachment->report_id}/{$attachment->stored_name}", $attachment->path);
        $this->assertSame('image/jpeg', $attachment->mime_type);
        $this->assertSame($photo->getSize(), $attachment->size);
    }

    public function test_uploaded_files_exist_on_public_disk(): void
    {
        $this->submitManualReport([
            'photos' => [UploadedFile::fake()->image('bukti.png')],
        ]);

        Storage::disk('public')->assertExists(ReportAttachment::query()->sole()->path);
    }

    public function test_deleting_report_deletes_attachment_records(): void
    {
        [$report] = $this->createReportWithAttachment();

        $report->delete();

        $this->assertDatabaseCount('report_attachments', 0);
    }

    public function test_deleting_report_deletes_physical_attachment_files(): void
    {
        [$report, $attachment] = $this->createReportWithAttachment();
        $directory = "reports/{$report->id}";

        Storage::disk('public')->assertExists($attachment->path);

        $report->delete();

        Storage::disk('public')->assertMissing($attachment->path);
        $this->assertFalse(Storage::disk('public')->directoryExists($directory));
    }

    public function test_deleting_report_succeeds_when_attachment_file_is_already_missing(): void
    {
        [$report, $attachment] = $this->createReportWithAttachment();
        Storage::disk('public')->delete($attachment->path);

        $deleted = $report->delete();

        $this->assertTrue($deleted);
        $this->assertDatabaseMissing('reports', ['id' => $report->id]);
        $this->assertDatabaseCount('report_attachments', 0);
    }

    public function test_public_tracking_displays_attachments(): void
    {
        [$report] = $this->createReportWithAttachment();

        $this->post(route('tracking.store'), [
            'ticket_number' => $report->ticket_number,
            'phone' => '081234567890',
        ])->assertOk()
            ->assertSee('Foto Laporan')
            ->assertSee("/report-attachments/{$report->attachments->first()->id}", false);
    }

    public function test_public_tracking_does_not_expose_internal_paths(): void
    {
        [$report, $attachment] = $this->createReportWithAttachment();

        $this->post(route('tracking.store'), [
            'ticket_number' => $report->ticket_number,
            'phone' => '081234567890',
        ])->assertOk()
            ->assertDontSee($attachment->path)
            ->assertDontSee($attachment->stored_name);
    }

    public function test_admin_detail_displays_attachments(): void
    {
        [$report] = $this->createReportWithAttachment();

        $this->actingAs(User::factory()->create())
            ->get(route('reports.show', $report))
            ->assertOk()
            ->assertSee('Foto Laporan');
    }

    public function test_rt_detail_displays_attachments(): void
    {
        [$report] = $this->createReportWithAttachment();
        $rw = $report->rt->rw;
        $user = User::factory()->create([
            'role' => UserRole::RT,
            'rw_id' => $rw->id,
            'rt_id' => $report->rt_id,
        ]);

        $this->actingAs($user)
            ->get(route('rt.reports.show', $report))
            ->assertOk()
            ->assertSee('Foto Laporan');
    }

    public function test_rw_detail_displays_attachments(): void
    {
        [$report] = $this->createReportWithAttachment();
        $user = User::factory()->create([
            'role' => UserRole::RW,
            'rw_id' => $report->rt->rw_id,
            'rt_id' => null,
        ]);

        $this->actingAs($user)
            ->get(route('rw.reports.show', $report))
            ->assertOk()
            ->assertSee('Foto Laporan');
    }

    public function test_kelurahan_detail_displays_attachments(): void
    {
        [$report] = $this->createReportWithAttachment();
        $user = User::factory()->create(['role' => UserRole::KELURAHAN]);

        $this->actingAs($user)
            ->get(route('kelurahan.reports.show', $report))
            ->assertOk()
            ->assertSee('Foto Laporan');
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function submitManualReport(array $overrides = [])
    {
        $rw = $this->createRw();
        $rt = $this->createRt($rw);
        $payload = array_merge([
            'rt_id' => $rt->id,
            'citizen_name' => 'Warga Lampiran',
            'phone' => '0812 3456 7890',
            'title' => 'Laporan dengan foto',
            'description' => 'Deskripsi laporan dengan bukti foto.',
        ], $overrides);

        return $this->actingAs(User::factory()->create())
            ->post(route('reports.store'), $payload);
    }

    /**
     * @return array{Report, ReportAttachment}
     */
    private function createReportWithAttachment(): array
    {
        $rw = $this->createRw();
        $rt = $this->createRt($rw);
        $citizen = Citizen::factory()->for($rt)->create([
            'phone' => '081234567890',
            'phone_normalized' => '6281234567890',
        ]);
        $report = Report::factory()->create([
            'citizen_id' => $citizen->id,
            'rt_id' => $rt->id,
        ]);
        $path = "reports/{$report->id}/attachment-test.jpg";
        Storage::disk('public')->put($path, 'fake-image-content');
        $attachment = $report->attachments()->create([
            'original_name' => 'bukti.jpg',
            'stored_name' => 'attachment-test.jpg',
            'path' => $path,
            'mime_type' => 'image/jpeg',
            'size' => 18,
        ]);

        return [$report, $attachment];
    }

    private function createRw(): Rw
    {
        return Rw::query()->create(['code' => '001', 'name' => 'RW 001']);
    }

    private function createRt(Rw $rw): Rt
    {
        return Rt::query()->create([
            'rw_id' => $rw->id,
            'code' => '001',
            'name' => 'RT 001',
        ]);
    }
}
