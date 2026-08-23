<?php

namespace App\Services;

use App\Enums\LetterRequirementEvidenceType;
use App\Enums\LetterRequirementSubmissionStatus;
use App\Enums\LetterStatus;
use App\Enums\LetterTypeVersionStatus;
use App\Models\Citizen;
use App\Models\LetterRequirement;
use App\Models\LetterTypeDefinition;
use App\Models\LetterTypeVersion;
use App\Models\VillageLetter;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

final class DynamicLetterSubmissionService
{
    private const EVIDENCE_DISK = 'local';

    public function __construct(
        private readonly DynamicLetterFieldValidator $fieldValidator,
        private readonly ReporterPhoneHasher $phoneHasher,
    ) {}

    /**
     * @param  array<string, mixed>  $fieldInput
     * @param  array<string, mixed>  $requirementFiles
     */
    public function submit(
        LetterTypeDefinition $requestedType,
        int $requestedVersionId,
        string $normalizedPhone,
        array $fieldInput,
        array $requirementFiles,
    ): VillageLetter {
        $storedPaths = [];

        try {
            return DB::transaction(function () use (
                $requestedType,
                $requestedVersionId,
                $normalizedPhone,
                $fieldInput,
                $requirementFiles,
                &$storedPaths,
            ): VillageLetter {
                $type = LetterTypeDefinition::query()->lockForUpdate()->find($requestedType->getKey());

                if ($type === null || ! $type->is_active) {
                    throw ValidationException::withMessages([
                        'letter_type' => 'Jenis surat tidak aktif atau tidak tersedia untuk pengajuan baru.',
                    ]);
                }

                $version = LetterTypeVersion::query()
                    ->whereKey($requestedVersionId)
                    ->where('letter_type_id', $type->getKey())
                    ->where('status', LetterTypeVersionStatus::PUBLISHED->value)
                    ->with(['fieldDefinitions', 'requirements', 'workflowSteps'])
                    ->lockForUpdate()
                    ->first();

                if ($version === null) {
                    throw ValidationException::withMessages([
                        'letter_type_version_id' => 'Version formulir tidak valid, tidak published, atau bukan milik jenis surat ini.',
                    ]);
                }

                $citizen = Citizen::query()
                    ->with('rt.rw')
                    ->where('phone_normalized', $normalizedPhone)
                    ->lockForUpdate()
                    ->first();

                if ($citizen === null || ! $citizen->is_active || ! $citizen->rt?->isAvailableForService()) {
                    throw ValidationException::withMessages([
                        'phone' => 'Identitas atau wilayah layanan tidak dapat diverifikasi. Periksa data dan coba lagi.',
                    ]);
                }

                $fieldValues = $this->fieldValidator->validate($version->fieldDefinitions, $fieldInput);
                $validatedFiles = $this->validateRequirementFiles($version->requirements, $requirementFiles);
                $submittedAt = now();

                $letter = VillageLetter::query()->create([
                    'letter_type' => null,
                    'letter_type_id' => $type->getKey(),
                    'letter_type_version_id' => $version->getKey(),
                    'required_approval_level' => null,
                    'citizen_id' => $citizen->getKey(),
                    'rt_id' => $citizen->rt_id,
                    'submitted_by' => null,
                    'purpose' => 'Pengajuan '.$type->name,
                    'notes' => null,
                    'status' => LetterStatus::SUBMITTED,
                    'submitted_at' => $submittedAt,
                ]);

                $submission = $letter->submission()->create([
                    'applicant_phone_hash' => $this->phoneHasher->hash($normalizedPhone),
                    'letter_type_code' => $type->code,
                    'letter_type_name' => $type->name,
                    'letter_type_description' => $type->description,
                    'version_number' => $version->version,
                    'configuration_snapshot' => $this->configurationSnapshot($type, $version),
                    'submitted_at' => $submittedAt,
                ]);

                foreach ($fieldValues as $fieldValue) {
                    $definition = $fieldValue['definition'];
                    $submission->fieldValues()->create([
                        'letter_field_definition_id' => $definition->getKey(),
                        'field_key' => $definition->key,
                        'field_label' => $definition->label,
                        'field_type' => $definition->field_type,
                        'sequence' => $definition->sequence,
                        'submitted_value' => $fieldValue['value'],
                    ]);
                }

                foreach ($version->requirements as $requirement) {
                    if ($requirement->evidence_type === LetterRequirementEvidenceType::UNCONFIGURED) {
                        throw ValidationException::withMessages([
                            'requirements' => 'Konfigurasi persyaratan belum aman untuk menerima pengajuan.',
                        ]);
                    }

                    $file = $validatedFiles[$requirement->key] ?? null;
                    $status = match ($requirement->evidence_type) {
                        LetterRequirementEvidenceType::MASTER_DATA => LetterRequirementSubmissionStatus::PENDING_VERIFICATION,
                        LetterRequirementEvidenceType::DOCUMENT_UPLOAD => $file instanceof UploadedFile
                            ? LetterRequirementSubmissionStatus::PROVIDED
                            : LetterRequirementSubmissionStatus::NOT_PROVIDED,
                        LetterRequirementEvidenceType::UNCONFIGURED => throw new RuntimeException('Unconfigured requirements cannot be persisted.'),
                    };

                    $requirementSubmission = $submission->requirements()->create([
                        'letter_requirement_id' => $requirement->getKey(),
                        'requirement_key' => $requirement->key,
                        'requirement_label' => $requirement->label,
                        'requirement_description' => $requirement->description,
                        'evidence_type' => $requirement->evidence_type,
                        'is_required' => $requirement->is_required,
                        'sequence' => $requirement->sequence,
                        'status' => $status,
                        'configuration_snapshot' => $requirement->configuration,
                    ]);

                    if ($file instanceof UploadedFile) {
                        $path = $this->evidencePath($file);
                        $storedPaths[] = $path;
                        $storedPath = $this->storeEvidence($file, $path);

                        if ($storedPath !== $path) {
                            $storedPaths[] = $storedPath;
                            throw new RuntimeException('Bukti persyaratan disimpan pada lokasi yang tidak diharapkan.');
                        }

                        $requirementSubmission->evidence()->create([
                            'disk' => self::EVIDENCE_DISK,
                            'path' => $path,
                            'stored_name' => basename($path),
                            'original_name' => Str::limit(basename(str_replace('\\', '/', $file->getClientOriginalName())), 255, ''),
                            'mime_type' => (string) $file->getMimeType(),
                            'size' => (int) $file->getSize(),
                            'sha256' => hash_file('sha256', $file->getRealPath()),
                        ]);
                    }
                }

                $letter->histories()->create([
                    'user_id' => null,
                    'old_status' => null,
                    'new_status' => LetterStatus::SUBMITTED,
                    'note' => null,
                ]);

                $submission->seal();

                return $letter->load([
                    'submission.fieldValues',
                    'submission.requirements.evidence',
                    'letterTypeDefinition',
                    'letterTypeVersion',
                ]);
            }, 1);
        } catch (Throwable $exception) {
            $this->cleanupEvidencePaths($storedPaths);

            throw $exception;
        }
    }

    /**
     * @param  Collection<int, LetterRequirement>  $requirements
     * @param  array<string, mixed>  $files
     * @return array<string, UploadedFile>
     */
    private function validateRequirementFiles($requirements, array $files): array
    {
        $documentRequirements = $requirements
            ->where('evidence_type', LetterRequirementEvidenceType::DOCUMENT_UPLOAD)
            ->keyBy('key');
        $unknownKeys = array_values(array_diff(array_keys($files), $documentRequirements->keys()->all()));

        if ($unknownKeys !== []) {
            throw ValidationException::withMessages([
                'requirements' => 'Bukti persyaratan tidak dikenal atau bukan berupa dokumen unggahan.',
            ]);
        }

        $rules = [];
        foreach ($documentRequirements as $requirement) {
            $rules['requirements.'.$requirement->key] = [
                $requirement->is_required ? 'required' : 'nullable',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'mimetypes:application/pdf,image/jpeg,image/png',
                'extensions:pdf,jpg,jpeg,png',
                'max:5120',
            ];
        }

        $validated = Validator::make(
            ['requirements' => $files],
            $rules,
            [],
            $documentRequirements->mapWithKeys(fn (LetterRequirement $requirement): array => [
                'requirements.'.$requirement->key => $requirement->label,
            ])->all(),
        )->validate();

        return $validated['requirements'] ?? [];
    }

    private function evidencePath(UploadedFile $file): string
    {
        $extension = match ($file->getMimeType()) {
            'application/pdf' => 'pdf',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            default => throw ValidationException::withMessages([
                'requirements' => 'Format bukti persyaratan tidak didukung.',
            ]),
        };

        return 'letter-evidence/'.now()->format('Y/m').'/'.Str::uuid().'.'.$extension;
    }

    private function storeEvidence(UploadedFile $file, string $path): string
    {
        $storedPath = Storage::disk(self::EVIDENCE_DISK)->putFileAs(
            dirname($path),
            $file,
            basename($path),
        );

        if (! is_string($storedPath)) {
            throw new RuntimeException('Bukti persyaratan gagal disimpan.');
        }

        return str_replace('\\', '/', $storedPath);
    }

    /** @param array<int, string> $paths */
    private function cleanupEvidencePaths(array $paths): void
    {
        foreach (array_unique($paths) as $path) {
            try {
                $disk = Storage::disk(self::EVIDENCE_DISK);
                $deleted = $disk->delete($path);
                $stillExists = $disk->exists($path);

                if ($deleted !== true || $stillExists) {
                    Log::critical('Phase 3 evidence cleanup failed.', [
                        'disk' => self::EVIDENCE_DISK,
                        'path' => $path,
                    ]);
                }
            } catch (Throwable) {
                Log::critical('Phase 3 evidence cleanup failed with a storage exception.', [
                    'disk' => self::EVIDENCE_DISK,
                    'path' => $path,
                ]);
            }
        }
    }

    /** @return array<string, mixed> */
    private function configurationSnapshot(LetterTypeDefinition $type, LetterTypeVersion $version): array
    {
        return [
            'schema_version' => 1,
            'source_version_snapshot' => $version->configuration_snapshot,
            'letter_type' => [
                'id' => $type->getKey(),
                'code' => $type->code,
                'name' => $type->name,
                'description' => $type->description,
            ],
            'version' => [
                'id' => $version->getKey(),
                'number' => $version->version,
                'published_at' => $version->published_at?->toIso8601String(),
            ],
            'fields' => $version->fieldDefinitions->map(fn ($field): array => [
                'id' => $field->getKey(),
                'key' => $field->key,
                'label' => $field->label,
                'type' => $field->field_type->value,
                'required' => $field->is_required,
                'sequence' => $field->sequence,
                'data_source' => $field->data_source?->value,
                'validation' => $field->validation,
                'configuration' => $field->configuration,
            ])->values()->all(),
            'requirements' => $version->requirements->map(fn ($requirement): array => [
                'id' => $requirement->getKey(),
                'key' => $requirement->key,
                'label' => $requirement->label,
                'description' => $requirement->description,
                'required' => $requirement->is_required,
                'evidence_type' => $requirement->evidence_type?->value,
                'sequence' => $requirement->sequence,
                'configuration' => $requirement->configuration,
            ])->values()->all(),
            'workflow' => $version->workflowSteps->map(fn ($step): array => [
                'id' => $step->getKey(),
                'sequence' => $step->sequence,
                'action' => $step->action->value,
                'actor_scope' => $step->actor_scope->value,
                'actor_role' => $step->actor_role?->value,
                'village_position' => $step->village_position?->value,
                'required' => $step->is_required,
                'configuration' => $step->configuration,
            ])->values()->all(),
        ];
    }
}
