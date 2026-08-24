<?php

namespace Tests\Feature;

use App\Enums\LetterApprovalLevel;
use App\Enums\LetterFieldType;
use App\Enums\LetterRequirementEvidenceType;
use App\Enums\LetterRequirementSubmissionStatus;
use App\Enums\LetterStatus;
use App\Enums\LetterType;
use App\Enums\LetterTypeVersionStatus;
use App\Enums\LetterWorkflowAction;
use App\Enums\LetterWorkflowActorScope;
use App\Enums\UserRole;
use App\Enums\VillagePosition;
use App\Models\Citizen;
use App\Models\LetterSubmission;
use App\Models\LetterTypeDefinition;
use App\Models\LetterTypeVersion;
use App\Models\Rt;
use App\Models\Rw;
use App\Models\User;
use App\Models\VillageLetter;
use App\Models\VillageLetterHistory;
use App\Policies\VillageLetterPolicy;
use App\Services\DynamicLetterSubmissionService;
use App\Services\LetterTypeVersionService;
use App\Services\VillageAnalyticsService;
use App\Services\VillageLetterWorkflow;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use LogicException;
use RuntimeException;
use Tests\TestCase;

class DynamicLetterSubmissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Storage::fake('local');
    }

    public function test_discovery_only_lists_active_types_with_a_published_version(): void
    {
        [$published] = $this->publishedType('AVAILABLE', 'Surat Tersedia');
        [$inactive] = $this->publishedType('INACTIVE', 'Surat Nonaktif');
        $inactive->update(['is_active' => false]);
        $draftOnly = LetterTypeDefinition::query()->create([
            'code' => 'DRAFT_ONLY',
            'name' => 'Surat Draft',
            'is_active' => true,
        ]);
        $draftOnly->versions()->create(['version' => 1]);

        $this->get(route('public.letter-submissions.index'))
            ->assertOk()
            ->assertSee($published->name)
            ->assertDontSee($inactive->name)
            ->assertDontSee($draftOnly->name);
    }

    public function test_form_renders_every_allowlisted_field_type_and_requirement_behavior(): void
    {
        [$type] = $this->publishedType('RENDER_ALL', 'Surat Renderer', [
            $this->field('short_text', 'Teks Singkat', LetterFieldType::TEXT, 10),
            $this->field('long_text', 'Teks Panjang', LetterFieldType::TEXTAREA, 20),
            $this->field('event_date', 'Tanggal Peristiwa', LetterFieldType::DATE, 30),
            $this->field('quantity', 'Jumlah', LetterFieldType::NUMBER, 40),
            $this->field('category', 'Kategori', LetterFieldType::SELECT, 50, configuration: ['options' => ['Aman', 'Darurat']]),
            $this->field('confirmed', 'Konfirmasi', LetterFieldType::BOOLEAN, 60),
        ], [
            $this->requirement('MASTER_IDENTITY', 'Identitas master', LetterRequirementEvidenceType::MASTER_DATA, 10),
            $this->requirement('SUPPORTING_DOC', 'Dokumen pendukung', LetterRequirementEvidenceType::DOCUMENT_UPLOAD, 20),
        ]);

        $this->get(route('public.letter-submissions.create', $type))
            ->assertOk()
            ->assertSee('name="fields[short_text]"', false)
            ->assertSee('name="fields[long_text]"', false)
            ->assertSee('type="date"', false)
            ->assertSee('type="number"', false)
            ->assertSee('Aman')
            ->assertSee('type="checkbox"', false)
            ->assertSee('name="requirements[SUPPORTING_DOC]"', false)
            ->assertSee('tidak dianggap otomatis terpenuhi');
    }

    public function test_submission_pins_version_fields_requirements_identity_and_private_upload(): void
    {
        $citizen = $this->citizen();
        [$type, $version] = $this->publishedType('COMPLETE', 'Surat Lengkap', [
            $this->field('short_text', 'Teks Singkat', LetterFieldType::TEXT, 10, validation: ['required' => true, 'max' => 20]),
            $this->field('long_text', 'Teks Panjang', LetterFieldType::TEXTAREA, 20),
            $this->field('event_date', 'Tanggal', LetterFieldType::DATE, 30),
            $this->field('quantity', 'Jumlah', LetterFieldType::NUMBER, 40),
            $this->field('category', 'Kategori', LetterFieldType::SELECT, 50, configuration: ['options' => ['A', 'B']]),
            $this->field('confirmed', 'Konfirmasi', LetterFieldType::BOOLEAN, 60),
        ], [
            $this->requirement('MASTER_IDENTITY', 'Identitas master', LetterRequirementEvidenceType::MASTER_DATA, 10),
            $this->requirement('SUPPORTING_DOC', 'Dokumen pendukung', LetterRequirementEvidenceType::DOCUMENT_UPLOAD, 20),
        ]);
        $file = UploadedFile::fake()->createWithContent('bukti.pdf', "%PDF-1.4\nPhase 3 evidence");

        $response = $this->post(route('public.letter-submissions.store', $type), [
            'letter_type_version_id' => $version->id,
            'phone' => '0812 3456 7890',
            'fields' => [
                'short_text' => 'Nilai singkat',
                'long_text' => 'Nilai panjang',
                'event_date' => '2026-08-23',
                'quantity' => '12.5',
                'category' => 'B',
                'confirmed' => '1',
            ],
            'requirements' => ['SUPPORTING_DOC' => $file],
        ]);

        $response->assertRedirect(route('public.letter-submissions.complete'));
        $letter = VillageLetter::query()->with('submission.fieldValues', 'submission.requirements.evidence')->sole();
        $this->get(route('public.letter-submissions.complete'))
            ->assertOk()
            ->assertSee($letter->public_tracking_code)
            ->assertSee('Surat Lengkap');
        $this->assertNull($letter->letter_type);
        $this->assertNull($letter->submitted_by);
        $this->assertNull($letter->required_approval_level);
        $this->assertSame($type->id, $letter->letter_type_id);
        $this->assertSame($version->id, $letter->letter_type_version_id);
        $this->assertSame($citizen->id, $letter->citizen_id);
        $this->assertSame(LetterStatus::SUBMITTED, $letter->status);
        $this->assertSame($version->id, $letter->submission->configuration_snapshot['version']['id']);
        $this->assertNotSame('6281234567890', $letter->submission->applicant_phone_hash);
        $this->assertSame(64, strlen($letter->submission->applicant_phone_hash));
        $this->assertNotNull($letter->submission->sealed_at);
        $this->assertSame(12.5, $letter->submission->fieldValues->firstWhere('field_key', 'quantity')->submitted_value);
        $this->assertTrue($letter->submission->fieldValues->firstWhere('field_key', 'confirmed')->submitted_value);

        $master = $letter->submission->requirements->firstWhere('requirement_key', 'MASTER_IDENTITY');
        $document = $letter->submission->requirements->firstWhere('requirement_key', 'SUPPORTING_DOC');
        $this->assertSame(LetterRequirementSubmissionStatus::PENDING_VERIFICATION, $master->status);
        $this->assertSame(LetterRequirementSubmissionStatus::PROVIDED, $document->status);
        $this->assertNotNull($document->evidence);
        $this->assertSame('local', $document->evidence->disk);
        Storage::disk('local')->assertExists($document->evidence->path);
        $this->assertStringStartsWith('letter-evidence/', $document->evidence->path);
        $this->assertStringNotContainsString('bukti.pdf', $document->evidence->path);
        $this->assertSame(1, $letter->histories()->count());
    }

    public function test_form_version_remains_pinned_after_a_newer_version_is_published(): void
    {
        $this->citizen();
        [$type, $versionOne] = $this->publishedType('PINNED', 'Nama Version Satu', [
            $this->field('old_field', 'Field Version Satu', LetterFieldType::TEXT, 10, required: true),
        ]);
        $versionTwo = app(LetterTypeVersionService::class)->createDraft($type, $this->villageSecretary());
        app(LetterTypeVersionService::class)->publish($versionTwo);

        $this->post(route('public.letter-submissions.store', $type), [
            'letter_type_version_id' => $versionOne->id,
            'phone' => '081234567890',
            'fields' => ['old_field' => 'Tetap memakai version satu'],
        ])->assertRedirect();

        $letter = VillageLetter::query()->with('submission.fieldValues')->sole();
        $this->assertSame($versionOne->id, $letter->letter_type_version_id);
        $this->assertSame(1, $letter->submission->version_number);
        $this->assertSame('Field Version Satu', $letter->submission->fieldValues->sole()->field_label);

        $type->update(['name' => 'Nama Master Diubah']);
        $this->assertSame('Nama Version Satu', $letter->fresh('submission')->typeLabel());
        $this->assertSame('Nama Version Satu', $letter->submission->configuration_snapshot['letter_type']['name']);
        $this->get(route('public.letter-submissions.create', $type))
            ->assertOk()
            ->assertSee('value="'.$versionTwo->id.'"', false);

        $type->update(['is_active' => false]);
        $this->assertSame('Nama Version Satu', $letter->fresh('submission')->typeLabel());
    }

    public function test_tampered_cross_type_draft_and_inactive_submissions_are_rejected(): void
    {
        $this->citizen();
        [$typeA, $publishedA] = $this->publishedType('TYPE_A', 'Tipe A');
        [$typeB, $publishedB] = $this->publishedType('TYPE_B', 'Tipe B');
        $draft = app(LetterTypeVersionService::class)->createDraft($typeA, $this->villageSecretary());

        $this->post(route('public.letter-submissions.store', $typeA), $this->payload($publishedB))
            ->assertSessionHasErrors('letter_type_version_id');
        $this->post(route('public.letter-submissions.store', $typeA), $this->payload($draft))
            ->assertSessionHasErrors('letter_type_version_id');

        $typeA->update(['is_active' => false]);
        $this->post(route('public.letter-submissions.store', $typeA), $this->payload($publishedA))
            ->assertSessionHasErrors('letter_type');
        $this->get(route('public.letter-submissions.create', $typeA))->assertNotFound();
        $this->assertDatabaseCount('village_letters', 0);
        $this->assertTrue($typeB->is_active);
    }

    public function test_dynamic_validation_rejects_missing_invalid_select_unknown_and_wrong_types(): void
    {
        $this->citizen();
        [$type, $version] = $this->publishedType('VALIDATE', 'Validasi Dinamis', [
            $this->field('required_text', 'Wajib', LetterFieldType::TEXT, 10, required: true),
            $this->field('choice', 'Pilihan', LetterFieldType::SELECT, 20, required: true, configuration: ['options' => ['A', 'B']]),
            $this->field('date_value', 'Tanggal', LetterFieldType::DATE, 30),
            $this->field('number_value', 'Angka', LetterFieldType::NUMBER, 40),
            $this->field('boolean_value', 'Boolean', LetterFieldType::BOOLEAN, 50),
        ]);

        $base = $this->payload($version);
        $this->post(route('public.letter-submissions.store', $type), $base)
            ->assertSessionHasErrors(['fields.required_text', 'fields.choice']);
        $this->post(route('public.letter-submissions.store', $type), [...$base, 'fields' => [
            'required_text' => 'Ada', 'choice' => 'FORGED',
        ]])->assertSessionHasErrors('fields.choice');
        $this->post(route('public.letter-submissions.store', $type), [...$base, 'fields' => [
            'required_text' => 'Ada', 'choice' => 'A', 'unknown' => 'tampered',
        ]])->assertSessionHasErrors('fields');
        $this->post(route('public.letter-submissions.store', $type), [...$base, 'fields' => [
            'required_text' => 'Ada', 'choice' => 'A', 'date_value' => 'not-a-date', 'number_value' => 'NaN', 'boolean_value' => 'maybe',
        ]])->assertSessionHasErrors(['fields.date_value', 'fields.number_value', 'fields.boolean_value']);
        $this->assertDatabaseCount('village_letters', 0);
    }

    public function test_identity_requires_an_exact_active_citizen_phone_match_without_nik_in_request(): void
    {
        $citizen = $this->citizen();
        [$type, $version] = $this->publishedType('IDENTITY', 'Identitas Aman');

        $this->post(route('public.letter-submissions.store', $type), [
            ...$this->payload($version),
            'phone' => '081200000000',
            'nik' => $citizen->nik,
            'name' => $citizen->name,
        ])->assertSessionHasErrors('phone');

        $citizen->update(['is_active' => false]);
        $this->post(route('public.letter-submissions.store', $type), $this->payload($version))
            ->assertSessionHasErrors('phone');
        $this->assertDatabaseCount('village_letters', 0);
    }

    public function test_document_upload_rejects_executable_content_and_uses_no_public_path(): void
    {
        $this->citizen();
        [$type, $version] = $this->publishedType('UPLOAD_SAFE', 'Upload Aman', requirements: [
            $this->requirement('DOCUMENT', 'Dokumen', LetterRequirementEvidenceType::DOCUMENT_UPLOAD, 10),
        ]);
        $executable = UploadedFile::fake()->createWithContent('malware.php', '<?php echo "unsafe";');

        $this->post(route('public.letter-submissions.store', $type), [
            ...$this->payload($version),
            'requirements' => ['DOCUMENT' => $executable],
        ])->assertSessionHasErrors('requirements.DOCUMENT');

        $this->assertDatabaseCount('village_letters', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
        $this->assertFalse(Storage::disk('public')->exists('letter-evidence'));
    }

    public function test_document_upload_requires_an_allowed_client_extension_in_addition_to_valid_mime(): void
    {
        $this->citizen();
        [$type, $version] = $this->publishedType('UPLOAD_EXTENSION', 'Upload Extension', requirements: [
            $this->requirement('DOCUMENT', 'Dokumen', LetterRequirementEvidenceType::DOCUMENT_UPLOAD, 10),
        ]);

        foreach ([
            'document.exe' => ['application/pdf', "%PDF-1.4\nvalid pdf content"],
            'document.txt' => ['application/pdf', "%PDF-1.4\nvalid pdf content"],
            'photo.php' => ['image/jpeg', "\xFF\xD8\xFF\xE0\x00\x10JFIF\x00\x01\xFF\xD9"],
        ] as $name => [$mimeType, $content]) {
            $file = UploadedFile::fake()->createWithContent($name, $content)->mimeType($mimeType);

            $this->post(route('public.letter-submissions.store', $type), [
                ...$this->payload($version),
                'requirements' => ['DOCUMENT' => $file],
            ])->assertSessionHasErrors('requirements.DOCUMENT');
        }

        $validPdf = UploadedFile::fake()
            ->createWithContent('document.pdf', "%PDF-1.4\nvalid pdf content")
            ->mimeType('application/pdf');

        $this->post(route('public.letter-submissions.store', $type), [
            ...$this->payload($version),
            'requirements' => ['DOCUMENT' => $validPdf],
        ])->assertRedirect(route('public.letter-submissions.complete'));

        $this->assertDatabaseCount('village_letters', 1);
        $evidence = VillageLetter::query()->with('submission.requirements.evidence')->sole()
            ->submission->requirements->sole()->evidence;
        $this->assertSame('pdf', pathinfo($evidence->path, PATHINFO_EXTENSION));
        Storage::disk('local')->assertExists($evidence->path);
    }

    public function test_exception_rolls_back_parent_children_and_stored_files(): void
    {
        $this->citizen();
        $type = LetterTypeDefinition::query()->create(['code' => 'ROLLBACK', 'name' => 'Rollback', 'is_active' => true]);
        $version = $type->versions()->create(['version' => 1]);
        $version->requirements()->create($this->requirement('DOCUMENT', 'Dokumen', LetterRequirementEvidenceType::DOCUMENT_UPLOAD, 10));
        $version->requirements()->create($this->requirement('UNSAFE', 'Belum aman', LetterRequirementEvidenceType::UNCONFIGURED, 20));
        $this->workflow($version);
        DB::table('letter_type_versions')->where('id', $version->id)->update([
            'status' => LetterTypeVersionStatus::PUBLISHED->value,
            'published_at' => now(),
            'configuration_snapshot' => json_encode(['schema_version' => 1], JSON_THROW_ON_ERROR),
        ]);
        $version->refresh();
        $file = UploadedFile::fake()->createWithContent('bukti.pdf', "%PDF-1.4\nrollback");

        try {
            app(DynamicLetterSubmissionService::class)->submit(
                $type,
                $version->id,
                '6281234567890',
                [],
                ['DOCUMENT' => $file],
            );
            $this->fail('Submission with an unconfigured published requirement unexpectedly succeeded.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('requirements', $exception->errors());
        }

        $this->assertDatabaseCount('village_letters', 0);
        $this->assertDatabaseCount('letter_submissions', 0);
        $this->assertDatabaseCount('letter_requirement_submissions', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_generic_tracking_uses_immutable_phone_hash_and_exposes_no_answers_or_evidence_path(): void
    {
        $citizen = $this->citizen();
        [$type, $version] = $this->publishedType('TRACKABLE', 'Surat Terlacak', [
            $this->field('private_answer', 'Jawaban Internal', LetterFieldType::TEXT, 10, required: true),
        ], [
            $this->requirement('MASTER_IDENTITY', 'Identitas master', LetterRequirementEvidenceType::MASTER_DATA, 10),
        ]);
        $this->post(route('public.letter-submissions.store', $type), [
            ...$this->payload($version),
            'fields' => ['private_answer' => 'RAHASIA-JAWABAN'],
        ])->assertRedirect();
        $letter = VillageLetter::query()->sole();
        $citizen->update(['phone' => '081299999999', 'phone_normalized' => '6281299999999']);

        $this->post(route('letter-tracking.store'), [
            'reference' => $letter->public_tracking_code,
            'phone' => '081234567890',
        ])->assertOk()
            ->assertSee('Surat Terlacak')
            ->assertSee('Perlu verifikasi petugas')
            ->assertDontSee('RAHASIA-JAWABAN')
            ->assertDontSee('letter-evidence')
            ->assertDontSee('source_version_snapshot');

        $this->post(route('letter-tracking.store'), [
            'reference' => $letter->public_tracking_code,
            'phone' => '081299999999',
        ])->assertOk()
            ->assertSee('Data belum dapat ditemukan.')
            ->assertDontSee('Surat Terlacak');
    }

    public function test_generic_pair_invariant_and_phase_four_actions_are_closed(): void
    {
        $citizen = $this->citizen();
        [$type, $version] = $this->publishedType('BOUNDARY', 'Boundary');

        $this->expectException(LogicException::class);
        VillageLetter::query()->create([
            'letter_type' => null,
            'letter_type_id' => null,
            'letter_type_version_id' => $version->id,
            'citizen_id' => $citizen->id,
            'rt_id' => $citizen->rt_id,
            'submitted_by' => null,
            'purpose' => 'Invalid pair',
            'status' => LetterStatus::SUBMITTED,
        ]);
    }

    public function test_generic_writer_does_not_enable_legacy_workflow_or_pdf(): void
    {
        $citizen = $this->citizen();
        [$type, $version] = $this->publishedType('NO_PHASE_FOUR', 'Tanpa Phase Empat');
        $this->post(route('public.letter-submissions.store', $type), $this->payload($version))->assertRedirect();
        $letter = VillageLetter::query()->sole();
        $rwUser = User::factory()->create([
            'role' => UserRole::RW,
            'position' => null,
            'rw_id' => $citizen->rt->rw_id,
            'rt_id' => null,
        ]);

        $this->actingAs($rwUser)->patch(route('rw.letters.review', $letter))->assertForbidden();
        $this->actingAs($rwUser)->get(route('rw.letters.pdf', $letter))->assertForbidden();

        try {
            app(VillageLetterWorkflow::class)->transition($letter, LetterStatus::RW_REVIEWED, $rwUser);
            $this->fail('Legacy workflow unexpectedly processed a generic submission.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('belum tersedia untuk pengajuan surat dinamis', $exception->getMessage());
        }
    }

    public function test_phase_three_rollback_refuses_before_any_destructive_operation(): void
    {
        $this->citizen();
        [$type, $version] = $this->publishedType('ROLLBACK_GATE', 'Rollback Gate', [
            $this->field('answer', 'Jawaban', LetterFieldType::TEXT, 10),
        ], [
            $this->requirement('DOCUMENT', 'Dokumen', LetterRequirementEvidenceType::DOCUMENT_UPLOAD, 10),
        ]);
        $letter = app(DynamicLetterSubmissionService::class)->submit(
            $type,
            $version->id,
            '6281234567890',
            ['answer' => 'Tetap utuh'],
            ['DOCUMENT' => UploadedFile::fake()->createWithContent('rollback.pdf', "%PDF-1.4\nrollback gate")],
        );
        $evidencePath = $letter->submission->requirements->sole()->evidence->path;
        $migration = require database_path('migrations/2026_08_23_000000_create_dynamic_letter_submissions.php');

        $this->assertLogicException(fn () => $migration->down());

        foreach (['letter_submissions', 'letter_field_values', 'letter_requirement_submissions', 'letter_requirement_evidences'] as $table) {
            $this->assertTrue(Schema::hasTable($table));
            $this->assertDatabaseCount($table, 1);
        }
        $this->assertDatabaseHas('village_letters', ['id' => $letter->id]);
        Storage::disk('local')->assertExists($evidencePath);
    }

    public function test_generic_submission_is_excluded_from_legacy_top_letter_analytics(): void
    {
        $this->citizen();
        [$type, $version] = $this->publishedType('ANALYTICS_NULL', 'Analytics Null');
        app(DynamicLetterSubmissionService::class)->submit($type, $version->id, '6281234567890', [], []);

        $analytics = app(VillageAnalyticsService::class)->village();

        $this->assertSame(1, $analytics['kpis']['letters']);
        $this->assertContains('Belum ada pengajuan surat bulan ini.', $analytics['insights']);
    }

    public function test_generic_discriminator_is_immutable_while_legacy_fields_remain_mutable(): void
    {
        $citizen = $this->citizen();
        [$type, $version] = $this->publishedType('IMMUTABLE', 'Immutable');
        [$otherType, $otherVersion] = $this->publishedType('OTHER_TYPE', 'Other Type');
        $letter = app(DynamicLetterSubmissionService::class)->submit($type, $version->id, '6281234567890', [], []);
        $submitter = User::factory()->create([
            'role' => UserRole::RT,
            'position' => null,
            'rw_id' => $citizen->rt->rw_id,
            'rt_id' => $citizen->rt_id,
        ]);

        foreach ([
            function () use ($letter): void {
                $letter->letter_type = LetterType::GENERAL_INTRODUCTION;
                $letter->save();
            },
            function () use ($letter, $submitter): void {
                $letter->submitted_by = $submitter->id;
                $letter->save();
            },
            function () use ($letter): void {
                $letter->required_approval_level = LetterApprovalLevel::KELURAHAN;
                $letter->save();
            },
            function () use ($letter, $otherType): void {
                $letter->letter_type_id = $otherType->id;
                $letter->save();
            },
            function () use ($letter, $otherVersion): void {
                $letter->letter_type_version_id = $otherVersion->id;
                $letter->save();
            },
        ] as $mutation) {
            $this->assertLogicException($mutation);
            $letter->refresh();
        }

        $legacySubmitter = User::factory()->create([
            'role' => UserRole::RT,
            'position' => null,
            'rw_id' => $citizen->rt->rw_id,
            'rt_id' => $citizen->rt_id,
        ]);
        $legacy = VillageLetter::query()->create([
            'letter_type' => LetterType::GENERAL_INTRODUCTION,
            'citizen_id' => $citizen->id,
            'rt_id' => $citizen->rt_id,
            'submitted_by' => $submitter->id,
            'purpose' => 'Legacy mutable',
            'status' => LetterStatus::DRAFT,
        ]);
        $legacy->update([
            'letter_type' => LetterType::RW_INTRODUCTION,
            'submitted_by' => $legacySubmitter->id,
            'required_approval_level' => LetterApprovalLevel::RW,
        ]);

        $this->assertSame(LetterType::RW_INTRODUCTION, $legacy->fresh()->letter_type);
        $this->assertSame($legacySubmitter->id, $legacy->submitted_by);
        $this->assertSame(LetterApprovalLevel::RW, $legacy->required_approval_level);
    }

    public function test_generic_reject_policy_has_an_explicit_denial(): void
    {
        $this->citizen();
        [$type, $version] = $this->publishedType('REJECT_DENIED', 'Reject Denied');
        $letter = app(DynamicLetterSubmissionService::class)->submit($type, $version->id, '6281234567890', [], []);
        $letter->status = LetterStatus::RW_REVIEWED;
        $letter->required_approval_level = LetterApprovalLevel::KELURAHAN;

        $this->assertFalse(app(VillageLetterPolicy::class)->reject($this->villageSecretary(), $letter));
    }

    public function test_sealed_snapshot_rejects_later_field_requirement_and_evidence_appends(): void
    {
        $this->citizen();
        [$type, $version] = $this->publishedType('SEALED_APPEND', 'Sealed Append', [
            $this->field('answer', 'Jawaban', LetterFieldType::TEXT, 10),
        ], [
            $this->requirement('DOCUMENT', 'Dokumen', LetterRequirementEvidenceType::DOCUMENT_UPLOAD, 10),
        ]);
        $letter = app(DynamicLetterSubmissionService::class)->submit(
            $type,
            $version->id,
            '6281234567890',
            ['answer' => 'Snapshot'],
            ['DOCUMENT' => UploadedFile::fake()->createWithContent('sealed.pdf', "%PDF-1.4\nsealed")],
        );
        $submission = $letter->submission;
        $definition = $version->fieldDefinitions->sole();
        $requirement = $version->requirements->sole();
        $requirementSnapshot = $submission->requirements->sole();

        $this->assertNotNull($submission->sealed_at);
        $this->assertLogicException(fn () => $submission->fieldValues()->create([
            'letter_field_definition_id' => $definition->id,
            'field_key' => 'late_field',
            'field_label' => 'Late Field',
            'field_type' => LetterFieldType::TEXT,
            'sequence' => 90,
            'submitted_value' => 'late',
        ]));
        $this->assertLogicException(fn () => $submission->requirements()->create([
            'letter_requirement_id' => $requirement->id,
            'requirement_key' => 'LATE_REQUIREMENT',
            'requirement_label' => 'Late Requirement',
            'evidence_type' => LetterRequirementEvidenceType::DOCUMENT_UPLOAD,
            'is_required' => false,
            'sequence' => 90,
            'status' => LetterRequirementSubmissionStatus::NOT_PROVIDED,
        ]));
        $this->assertLogicException(fn () => $requirementSnapshot->evidence()->create([
            'disk' => 'local',
            'path' => 'letter-evidence/forbidden.pdf',
            'stored_name' => 'forbidden.pdf',
            'original_name' => 'forbidden.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1,
            'sha256' => str_repeat('a', 64),
        ]));
    }

    public function test_sealed_snapshot_parent_and_children_reject_update_delete_and_relocation(): void
    {
        $this->citizen();
        [$type, $version] = $this->publishedType('SEALED_MUTATION', 'Sealed Mutation', [
            $this->field('answer', 'Jawaban', LetterFieldType::TEXT, 10),
        ], [
            $this->requirement('DOCUMENT', 'Dokumen', LetterRequirementEvidenceType::DOCUMENT_UPLOAD, 10),
        ]);
        $letter = app(DynamicLetterSubmissionService::class)->submit(
            $type,
            $version->id,
            '6281234567890',
            ['answer' => 'Snapshot'],
            ['DOCUMENT' => UploadedFile::fake()->createWithContent('mutation.pdf', "%PDF-1.4\nmutation")],
        );
        $submission = $letter->submission;
        $field = $submission->fieldValues->sole();
        $requirement = $submission->requirements->sole();
        $evidence = $requirement->evidence;

        $this->assertLogicException(fn () => $submission->update(['letter_type_name' => 'Relocated']));
        $this->assertLogicException(fn () => $submission->delete());
        $this->assertLogicException(fn () => $field->update(['letter_submission_id' => $submission->id + 1]));
        $this->assertLogicException(fn () => $field->delete());
        $this->assertLogicException(fn () => $requirement->update(['letter_submission_id' => $submission->id + 1]));
        $this->assertLogicException(fn () => $requirement->delete());
        $this->assertLogicException(fn () => $evidence->update(['letter_requirement_submission_id' => $requirement->id + 1]));
        $this->assertLogicException(fn () => $evidence->delete());
        $this->assertLogicException(fn () => $submission->seal());
    }

    public function test_stale_second_instance_cannot_reseal_or_rewrite_snapshot_timestamps(): void
    {
        $citizen = $this->citizen();
        [$type, $version] = $this->publishedType('ATOMIC_SEAL', 'Atomic Seal');
        $submittedAt = now();
        $letter = VillageLetter::query()->create([
            'letter_type' => null,
            'letter_type_id' => $type->id,
            'letter_type_version_id' => $version->id,
            'required_approval_level' => null,
            'citizen_id' => $citizen->id,
            'rt_id' => $citizen->rt_id,
            'submitted_by' => null,
            'purpose' => 'Atomic seal regression',
            'status' => LetterStatus::SUBMITTED,
            'submitted_at' => $submittedAt,
        ]);
        $submission = $letter->submission()->create([
            'applicant_phone_hash' => hash('sha256', '6281234567890'),
            'letter_type_code' => $type->code,
            'letter_type_name' => $type->name,
            'letter_type_description' => $type->description,
            'version_number' => $version->version,
            'configuration_snapshot' => [
                'schema_version' => 1,
                'source_version_snapshot' => $version->configuration_snapshot,
                'letter_type' => ['id' => $type->id, 'code' => $type->code, 'name' => $type->name],
                'version' => ['id' => $version->id, 'number' => $version->version],
                'fields' => [],
                'requirements' => [],
                'workflow' => $version->configuration_snapshot['workflow'],
            ],
            'submitted_at' => $submittedAt,
        ]);
        $letter->histories()->create([
            'user_id' => null,
            'old_status' => null,
            'new_status' => LetterStatus::SUBMITTED,
            'note' => null,
        ]);
        $first = LetterSubmission::query()->findOrFail($submission->id);
        $second = LetterSubmission::query()->findOrFail($submission->id);

        $this->assertNull($first->sealed_at);
        $this->assertNull($second->sealed_at);
        $first->seal();
        $afterFirstSeal = LetterSubmission::query()->findOrFail($submission->id);
        $sealedAt = $afterFirstSeal->getRawOriginal('sealed_at');
        $updatedAt = $afterFirstSeal->getRawOriginal('updated_at');
        $this->assertNotNull($first->sealed_at);

        $this->travel(1)->seconds();
        $this->assertLogicException(fn () => $second->seal());

        $persisted = LetterSubmission::query()->findOrFail($submission->id);
        $this->assertSame($sealedAt, $persisted->getRawOriginal('sealed_at'));
        $this->assertSame($updatedAt, $persisted->getRawOriginal('updated_at'));
        $this->assertNotNull($persisted->sealed_at);
        $this->assertSame($letter->id, $persisted->village_letter_id);
    }

    public function test_generic_village_letter_delete_is_rejected(): void
    {
        $this->citizen();
        [$type, $version] = $this->publishedType('DELETE_DENIED', 'Delete Denied');
        $letter = app(DynamicLetterSubmissionService::class)->submit($type, $version->id, '6281234567890', [], []);

        $this->assertLogicException(fn () => $letter->delete());
        $this->assertDatabaseHas('village_letters', ['id' => $letter->id]);
        $this->assertDatabaseHas('letter_submissions', ['village_letter_id' => $letter->id]);
    }

    public function test_public_identity_and_inactive_territory_failures_share_one_generic_message(): void
    {
        $citizen = $this->citizen();
        [$type, $version] = $this->publishedType('GENERIC_IDENTITY', 'Generic Identity');
        $expected = 'Identitas atau wilayah layanan tidak dapat diverifikasi. Periksa data dan coba lagi.';

        $this->post(route('public.letter-submissions.store', $type), [
            ...$this->payload($version),
            'phone' => '081200000000',
        ])->assertSessionHasErrors(['phone' => $expected]);

        $citizen->update(['is_active' => false]);
        $this->post(route('public.letter-submissions.store', $type), $this->payload($version))
            ->assertSessionHasErrors(['phone' => $expected]);

        $citizen->update(['is_active' => true]);
        $citizen->rt->update(['is_active' => false]);
        $this->post(route('public.letter-submissions.store', $type), $this->payload($version))
            ->assertSessionHasErrors(['phone' => $expected]);

        $citizen->rt->update(['is_active' => true]);
        $citizen->rt->rw->update(['is_active' => false]);
        $this->post(route('public.letter-submissions.store', $type), $this->payload($version))
            ->assertSessionHasErrors(['phone' => $expected]);

        $this->assertDatabaseCount('village_letters', 0);
    }

    public function test_legacy_tracking_uses_only_the_citizens_current_phone(): void
    {
        $citizen = $this->citizen();
        $submitter = User::factory()->create([
            'role' => UserRole::RT,
            'position' => null,
            'rw_id' => $citizen->rt->rw_id,
            'rt_id' => $citizen->rt_id,
        ]);
        $letter = VillageLetter::query()->create([
            'letter_type' => LetterType::GENERAL_INTRODUCTION,
            'citizen_id' => $citizen->id,
            'rt_id' => $citizen->rt_id,
            'submitted_by' => $submitter->id,
            'purpose' => 'Legacy tracking',
            'status' => LetterStatus::DRAFT,
        ]);
        $citizen->update(['phone' => '081299999999', 'phone_normalized' => '6281299999999']);

        $this->post(route('letter-tracking.store'), [
            'reference' => $letter->public_tracking_code,
            'phone' => '081234567890',
        ])->assertOk()->assertSee('Data belum dapat ditemukan.');
        $this->post(route('letter-tracking.store'), [
            'reference' => $letter->public_tracking_code,
            'phone' => '081299999999',
        ])->assertOk()->assertSee(LetterType::GENERAL_INTRODUCTION->label());
    }

    public function test_multiple_uploads_are_cleaned_when_a_later_requirement_fails(): void
    {
        $this->citizen();
        $type = LetterTypeDefinition::query()->create(['code' => 'MULTI_CLEANUP', 'name' => 'Multi Cleanup', 'is_active' => true]);
        $version = $type->versions()->create(['version' => 1]);
        $version->requirements()->create($this->requirement('DOCUMENT_A', 'Dokumen A', LetterRequirementEvidenceType::DOCUMENT_UPLOAD, 10));
        $version->requirements()->create($this->requirement('DOCUMENT_B', 'Dokumen B', LetterRequirementEvidenceType::DOCUMENT_UPLOAD, 20));
        $version->requirements()->create($this->requirement('UNSAFE', 'Belum aman', LetterRequirementEvidenceType::UNCONFIGURED, 30));
        $this->workflow($version);
        DB::table('letter_type_versions')->where('id', $version->id)->update([
            'status' => LetterTypeVersionStatus::PUBLISHED->value,
            'published_at' => now(),
            'configuration_snapshot' => json_encode(['schema_version' => 1], JSON_THROW_ON_ERROR),
        ]);

        try {
            app(DynamicLetterSubmissionService::class)->submit(
                $type,
                $version->id,
                '6281234567890',
                [],
                [
                    'DOCUMENT_A' => UploadedFile::fake()->createWithContent('a.pdf', "%PDF-1.4\nA"),
                    'DOCUMENT_B' => UploadedFile::fake()->createWithContent('b.pdf', "%PDF-1.4\nB"),
                ],
            );
            $this->fail('The unsafe trailing requirement unexpectedly succeeded.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('requirements', $exception->errors());
        }

        $this->assertSame([], Storage::disk('local')->allFiles());
        $this->assertDatabaseCount('village_letters', 0);
    }

    public function test_failed_write_still_attempts_cleanup_of_the_precomputed_path(): void
    {
        $this->citizen();
        [$type, $version] = $this->publishedType('FAILED_WRITE', 'Failed Write', requirements: [
            $this->requirement('DOCUMENT', 'Dokumen', LetterRequirementEvidenceType::DOCUMENT_UPLOAD, 10),
        ]);
        $knownPath = null;
        $cleanupPath = null;
        $checkedPath = null;
        $disk = \Mockery::mock();
        $disk->shouldReceive('putFileAs')->once()->andReturnUsing(function (string $directory, UploadedFile $file, string $name) use (&$knownPath): false {
            $knownPath = str_replace('\\', '/', $directory.'/'.$name);

            return false;
        });
        $disk->shouldReceive('delete')->once()->andReturnUsing(function (string $path) use (&$cleanupPath): false {
            $cleanupPath = $path;

            return false;
        });
        $disk->shouldReceive('exists')->once()->andReturnUsing(function (string $path) use (&$checkedPath): false {
            $checkedPath = $path;

            return false;
        });
        Storage::shouldReceive('disk')->twice()->with('local')->andReturn($disk);
        Log::shouldReceive('critical')->once()->withArgs(function (string $message, array $context) use (&$knownPath): bool {
            return $message === 'Phase 3 evidence cleanup failed.'
                && $context === ['disk' => 'local', 'path' => $knownPath];
        });

        try {
            app(DynamicLetterSubmissionService::class)->submit(
                $type,
                $version->id,
                '6281234567890',
                [],
                ['DOCUMENT' => UploadedFile::fake()->createWithContent('failed.pdf', "%PDF-1.4\nfailed")],
            );
            $this->fail('The simulated failed write unexpectedly succeeded.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Bukti persyaratan gagal disimpan.', $exception->getMessage());
        }

        $this->assertNotNull($knownPath);
        $this->assertStringStartsWith('letter-evidence/', $knownPath);
        $this->assertStringEndsWith('.pdf', $knownPath);
        $this->assertSame($knownPath, $cleanupPath);
        $this->assertSame($knownPath, $checkedPath);
        $this->assertDatabaseCount('village_letters', 0);
    }

    public function test_history_failure_rolls_back_database_and_cleans_stored_evidence(): void
    {
        $this->citizen();
        [$type, $version] = $this->publishedType('HISTORY_CLEANUP', 'History Cleanup', requirements: [
            $this->requirement('DOCUMENT', 'Dokumen', LetterRequirementEvidenceType::DOCUMENT_UPLOAD, 10),
        ]);
        $event = 'eloquent.creating: '.VillageLetterHistory::class;
        Event::listen($event, function (): void {
            throw new RuntimeException('Simulated history failure.');
        });

        try {
            app(DynamicLetterSubmissionService::class)->submit(
                $type,
                $version->id,
                '6281234567890',
                [],
                ['DOCUMENT' => UploadedFile::fake()->createWithContent('history.pdf', "%PDF-1.4\nhistory")],
            );
            $this->fail('The simulated history failure unexpectedly succeeded.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulated history failure.', $exception->getMessage());
        } finally {
            Event::forget($event);
        }

        $this->assertSame([], Storage::disk('local')->allFiles());
        $this->assertDatabaseCount('village_letters', 0);
        $this->assertDatabaseCount('letter_submissions', 0);
    }

    public function test_staff_index_and_show_mark_generic_submissions(): void
    {
        $citizen = $this->citizen();
        [$type, $version] = $this->publishedType('STAFF_MARKER', 'Staff Marker');
        $letter = app(DynamicLetterSubmissionService::class)->submit($type, $version->id, '6281234567890', [], []);
        $rwUser = User::factory()->create([
            'role' => UserRole::RW,
            'position' => null,
            'rw_id' => $citizen->rt->rw_id,
            'rt_id' => null,
        ]);

        $this->actingAs($rwUser)->get(route('rw.letters.index'))->assertOk()->assertSee('Pengajuan Dinamis');
        $this->actingAs($rwUser)->get(route('rw.letters.show', $letter))->assertOk()->assertSee('Pengajuan Dinamis');
    }

    /**
     * @param  array<int, array<string, mixed>>  $fields
     * @param  array<int, array<string, mixed>>  $requirements
     * @return array{LetterTypeDefinition, LetterTypeVersion}
     */
    public function test_generic_submission_can_be_approved_signed_issued_and_downloaded_as_pdf(): void
    {
        $this->citizen();

        [$type, $version] = $this->publishedType(
            'DOMISILI_DEMO',
            'Surat Keterangan Domisili',
            [
                $this->field(
                    'keperluan',
                    'Keperluan',
                    LetterFieldType::TEXT,
                    10,
                    required: true,
                ),
            ],
        );

        $letter = app(DynamicLetterSubmissionService::class)->submit(
            $type,
            $version->id,
            '6281234567890',
            ['keperluan' => 'Keperluan administrasi'],
            [],
        );

        $secretary = $this->villageSecretary();

        $head = User::factory()->create([
            'role' => UserRole::KELURAHAN,
            'position' => VillagePosition::VILLAGE_HEAD,
            'rw_id' => null,
            'rt_id' => null,
        ]);

        $this->actingAs($secretary)
            ->patch(route('kelurahan.letters.approve', $letter))
            ->assertRedirect();

        $this->assertSame(
            LetterStatus::APPROVED,
            $letter->fresh()->status,
        );

        $this->actingAs($head)
            ->patch(route('kelurahan.letters.sign', $letter))
            ->assertRedirect();

        $this->assertSame(
            LetterStatus::SIGNED,
            $letter->fresh()->status,
        );

        $this->actingAs($secretary)
            ->patch(route('kelurahan.letters.issue', $letter))
            ->assertRedirect();

        $letter->refresh();

        $this->assertSame(LetterStatus::ISSUED, $letter->status);
        $this->assertNotNull($letter->letter_number);
        $this->assertNotNull($letter->issued_at);

        $this->actingAs($secretary)
            ->get(route('kelurahan.letters.pdf', $letter))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }
    private function publishedType(string $code, string $name, array $fields = [], array $requirements = []): array
    {
        $type = LetterTypeDefinition::query()->create([
            'code' => $code,
            'name' => $name,
            'description' => 'Deskripsi '.$name,
            'is_active' => true,
        ]);
        $version = $type->versions()->create(['version' => 1]);

        foreach ($fields as $field) {
            $version->fieldDefinitions()->create($field);
        }
        foreach ($requirements as $requirement) {
            $version->requirements()->create($requirement);
        }
        $this->workflow($version);

        return [$type, app(LetterTypeVersionService::class)->publish($version)];
    }

    private function workflow(LetterTypeVersion $version): void
    {
        foreach ([
            [10, LetterWorkflowAction::SUBMIT, LetterWorkflowActorScope::CITIZEN, null, null],
            [20, LetterWorkflowAction::APPROVE, LetterWorkflowActorScope::KELURAHAN, UserRole::KELURAHAN, VillagePosition::VILLAGE_SECRETARY],
            [30, LetterWorkflowAction::SIGN, LetterWorkflowActorScope::KELURAHAN, UserRole::KELURAHAN, VillagePosition::VILLAGE_HEAD],
            [40, LetterWorkflowAction::ISSUE, LetterWorkflowActorScope::KELURAHAN, UserRole::KELURAHAN, VillagePosition::VILLAGE_SECRETARY],
        ] as [$sequence, $action, $scope, $role, $position]) {
            $version->workflowSteps()->create([
                'sequence' => $sequence,
                'action' => $action,
                'actor_scope' => $scope,
                'actor_role' => $role,
                'village_position' => $position,
                'is_required' => true,
            ]);
        }
    }

    /** @return array<string, mixed> */
    private function field(
        string $key,
        string $label,
        LetterFieldType $type,
        int $sequence,
        bool $required = false,
        ?array $validation = null,
        ?array $configuration = null,
    ): array {
        return [
            'key' => $key,
            'label' => $label,
            'field_type' => $type,
            'is_required' => $required,
            'sequence' => $sequence,
            'data_source' => null,
            'validation' => $validation,
            'configuration' => $configuration,
        ];
    }

    /** @return array<string, mixed> */
    private function requirement(
        string $key,
        string $label,
        LetterRequirementEvidenceType $type,
        int $sequence,
        bool $required = true,
    ): array {
        return [
            'key' => $key,
            'label' => $label,
            'description' => 'Deskripsi '.$label,
            'is_required' => $required,
            'evidence_type' => $type,
            'sequence' => $sequence,
            'configuration' => null,
        ];
    }

    private function citizen(): Citizen
    {
        $rw = Rw::query()->create(['code' => '001', 'name' => 'RW 001']);
        $rt = Rt::query()->create(['rw_id' => $rw->id, 'code' => '001', 'name' => 'RT 001']);

        return Citizen::factory()->for($rt)->create([
            'phone' => '081234567890',
            'phone_normalized' => '6281234567890',
            'is_active' => true,
        ]);
    }

    private function villageSecretary(): User
    {
        return User::factory()->create([
            'role' => UserRole::KELURAHAN,
            'position' => VillagePosition::VILLAGE_SECRETARY,
            'rw_id' => null,
            'rt_id' => null,
        ]);
    }

    /** @return array<string, mixed> */
    private function payload(LetterTypeVersion $version): array
    {
        return [
            'letter_type_version_id' => $version->id,
            'phone' => '081234567890',
            'fields' => [],
        ];
    }

    private function assertLogicException(callable $callback): void
    {
        try {
            $callback();
            $this->fail('Expected a LogicException from the immutable Phase 3 boundary.');
        } catch (LogicException) {
            $this->addToAssertionCount(1);
        }
    }
}
