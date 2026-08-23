<?php

namespace App\Services;

use App\Enums\LetterTypeVersionStatus;
use App\Models\LetterTypeDefinition;
use App\Models\LetterTypeVersion;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LetterTypeVersionService
{
    public function __construct(private readonly LetterTypePublishValidator $validator) {}

    public function createDraft(LetterTypeDefinition $letterType, User $creator): LetterTypeVersion
    {
        return DB::transaction(function () use ($letterType, $creator): LetterTypeVersion {
            $lockedType = LetterTypeDefinition::query()->lockForUpdate()->findOrFail($letterType->id);
            $existingDraft = $lockedType->versions()
                ->where('status', LetterTypeVersionStatus::DRAFT->value)
                ->lockForUpdate()
                ->first();

            if ($existingDraft !== null) {
                throw ValidationException::withMessages([
                    'version' => "Jenis surat ini masih mempunyai draft version {$existingDraft->version}.",
                ]);
            }

            $nextVersion = ((int) $lockedType->versions()->max('version')) + 1;
            $version = $lockedType->versions()->create([
                'version' => $nextVersion,
                'status' => LetterTypeVersionStatus::DRAFT,
                'created_by_user_id' => $creator->id,
            ]);

            $published = $lockedType->versions()
                ->where('status', LetterTypeVersionStatus::PUBLISHED->value)
                ->orderByDesc('version')
                ->with(['requirements', 'fieldDefinitions', 'workflowSteps'])
                ->first();

            if ($published !== null) {
                $this->cloneConfiguration($published, $version);
            }

            return $version->load(['requirements', 'fieldDefinitions', 'workflowSteps']);
        }, 3);
    }

    public function publish(LetterTypeVersion $version): LetterTypeVersion
    {
        return DB::transaction(function () use ($version): LetterTypeVersion {
            LetterTypeDefinition::query()->whereKey($version->letter_type_id)->lockForUpdate()->firstOrFail();
            $locked = LetterTypeVersion::query()->lockForUpdate()->findOrFail($version->id);
            $this->validator->assertPublishable($locked);

            $locked->publishValidatedConfiguration(
                fn ($publishedAt): array => $this->snapshot($locked, $publishedAt->toIso8601String()),
            );

            return $locked->refresh()->load(['requirements', 'fieldDefinitions', 'workflowSteps']);
        }, 3);
    }

    public function deleteDraft(LetterTypeVersion $version): void
    {
        DB::transaction(function () use ($version): void {
            LetterTypeDefinition::query()->whereKey($version->letter_type_id)->lockForUpdate()->firstOrFail();
            $locked = LetterTypeVersion::query()->lockForUpdate()->findOrFail($version->id);

            if ($locked->status !== LetterTypeVersionStatus::DRAFT) {
                throw ValidationException::withMessages([
                    'version' => 'Published version tidak dapat dihapus. Buat version baru untuk perubahan berikutnya.',
                ]);
            }

            if ($locked->letters()->exists()) {
                throw ValidationException::withMessages([
                    'version' => 'Version yang sudah direferensikan surat tidak dapat dihapus.',
                ]);
            }

            $locked->delete();
        }, 3);
    }

    private function cloneConfiguration(LetterTypeVersion $source, LetterTypeVersion $target): void
    {
        foreach ($source->requirements as $requirement) {
            $target->requirements()->create([
                'key' => $requirement->key,
                'label' => $requirement->label,
                'description' => $requirement->description,
                'is_required' => $requirement->is_required,
                'evidence_type' => $requirement->evidence_type?->value,
                'sequence' => $requirement->sequence,
                'configuration' => $requirement->configuration,
            ]);
        }

        foreach ($source->fieldDefinitions as $field) {
            $target->fieldDefinitions()->create([
                'key' => $field->key,
                'label' => $field->label,
                'field_type' => $field->field_type->value,
                'is_required' => $field->is_required,
                'sequence' => $field->sequence,
                'data_source' => $field->data_source?->value,
                'validation' => $field->validation,
                'configuration' => $field->configuration,
            ]);
        }

        foreach ($source->workflowSteps as $step) {
            $target->workflowSteps()->create([
                'sequence' => $step->sequence,
                'action' => $step->action->value,
                'actor_scope' => $step->actor_scope->value,
                'actor_role' => $step->actor_role?->value,
                'village_position' => $step->village_position?->value,
                'is_required' => $step->is_required,
                'configuration' => $step->configuration,
            ]);
        }
    }

    private function snapshot(LetterTypeVersion $version, string $publishedAt): array
    {
        $version->load(['typeDefinition', 'requirements', 'fieldDefinitions', 'workflowSteps']);

        return [
            'schema_version' => 1,
            'published_at' => $publishedAt,
            'letter_type' => [
                'code' => $version->typeDefinition->code,
                'name' => $version->typeDefinition->name,
                'description' => $version->typeDefinition->description,
            ],
            'requirements' => $version->requirements->map(fn ($requirement): array => [
                'key' => $requirement->key,
                'label' => $requirement->label,
                'description' => $requirement->description,
                'required' => $requirement->is_required,
                'evidence_type' => $requirement->evidence_type?->value,
                'sequence' => $requirement->sequence,
                'configuration' => $requirement->configuration,
            ])->values()->all(),
            'fields' => $version->fieldDefinitions->map(fn ($field): array => [
                'key' => $field->key,
                'label' => $field->label,
                'type' => $field->field_type->value,
                'required' => $field->is_required,
                'sequence' => $field->sequence,
                'data_source' => $field->data_source?->value,
                'validation' => $field->validation,
                'configuration' => $field->configuration,
            ])->values()->all(),
            'workflow' => $version->workflowSteps->map(fn ($step): array => [
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
