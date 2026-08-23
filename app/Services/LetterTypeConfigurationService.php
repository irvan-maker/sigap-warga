<?php

namespace App\Services;

use App\Enums\LetterFieldDataSource;
use App\Enums\LetterFieldType;
use App\Enums\LetterRequirementEvidenceType;
use App\Enums\LetterTypeVersionStatus;
use App\Enums\LetterWorkflowAction;
use App\Enums\LetterWorkflowActorScope;
use App\Enums\UserRole;
use App\Enums\VillagePosition;
use App\Models\LetterFieldDefinition;
use App\Models\LetterRequirement;
use App\Models\LetterTypeDefinition;
use App\Models\LetterTypeVersion;
use App\Models\LetterWorkflowStep;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LetterTypeConfigurationService
{
    private const REQUIREMENT_MUTABLE_ATTRIBUTES = [
        'key', 'label', 'description', 'is_required', 'evidence_type', 'sequence', 'configuration',
    ];

    private const FIELD_MUTABLE_ATTRIBUTES = [
        'key', 'label', 'field_type', 'is_required', 'sequence', 'data_source', 'validation', 'configuration',
    ];

    private const WORKFLOW_MUTABLE_ATTRIBUTES = [
        'sequence', 'action', 'actor_scope', 'village_position', 'is_required', 'configuration',
    ];

    public function __construct(private readonly LetterTypePublishValidator $validator) {}

    public function createRequirement(LetterTypeVersion $version, array $attributes): LetterRequirement
    {
        $attributes = $this->prepareRequirementAttributes($attributes);

        return $this->withLockedDraft($version, fn (LetterTypeVersion $locked) => $locked->requirements()->create($attributes));
    }

    public function updateRequirement(
        LetterTypeVersion $version,
        LetterRequirement $requirement,
        array $attributes,
    ): LetterRequirement {
        $attributes = $this->prepareRequirementAttributes($attributes);

        return $this->withLockedDraft($version, function () use ($version, $requirement, $attributes): LetterRequirement {
            $locked = LetterRequirement::query()->lockForUpdate()->findOrFail($requirement->id);
            $this->assertChildBelongsToVersion($locked->letter_type_version_id, $version);
            $locked->update($attributes);

            return $locked->refresh();
        });
    }

    public function deleteRequirement(LetterTypeVersion $version, LetterRequirement $requirement): void
    {
        $this->withLockedDraft($version, function () use ($version, $requirement): void {
            $locked = LetterRequirement::query()->lockForUpdate()->findOrFail($requirement->id);
            $this->assertChildBelongsToVersion($locked->letter_type_version_id, $version);
            $locked->delete();
        });
    }

    public function createField(LetterTypeVersion $version, array $attributes): LetterFieldDefinition
    {
        $attributes = $this->prepareFieldAttributes($attributes);

        return $this->withLockedDraft($version, fn (LetterTypeVersion $locked) => $locked->fieldDefinitions()->create($attributes));
    }

    public function updateField(
        LetterTypeVersion $version,
        LetterFieldDefinition $field,
        array $attributes,
    ): LetterFieldDefinition {
        $this->rejectParentRelocation($attributes);

        return $this->withLockedDraft($version, function () use ($version, $field, $attributes): LetterFieldDefinition {
            $locked = LetterFieldDefinition::query()->lockForUpdate()->findOrFail($field->id);
            $this->assertChildBelongsToVersion($locked->letter_type_version_id, $version);
            $attributes = $this->prepareFieldAttributes($attributes, $locked);
            $locked->update($attributes);

            return $locked->refresh();
        });
    }

    public function deleteField(LetterTypeVersion $version, LetterFieldDefinition $field): void
    {
        $this->withLockedDraft($version, function () use ($version, $field): void {
            $locked = LetterFieldDefinition::query()->lockForUpdate()->findOrFail($field->id);
            $this->assertChildBelongsToVersion($locked->letter_type_version_id, $version);
            $locked->delete();
        });
    }

    public function createWorkflowStep(LetterTypeVersion $version, array $attributes): LetterWorkflowStep
    {
        $attributes = $this->prepareWorkflowAttributes($attributes);

        return $this->withLockedDraft($version, fn (LetterTypeVersion $locked) => $locked->workflowSteps()->create($attributes));
    }

    public function updateWorkflowStep(
        LetterTypeVersion $version,
        LetterWorkflowStep $step,
        array $attributes,
    ): LetterWorkflowStep {
        $attributes = $this->prepareWorkflowAttributes($attributes);

        return $this->withLockedDraft($version, function () use ($version, $step, $attributes): LetterWorkflowStep {
            $locked = LetterWorkflowStep::query()->lockForUpdate()->findOrFail($step->id);
            $this->assertChildBelongsToVersion($locked->letter_type_version_id, $version);
            $locked->update($attributes);

            return $locked->refresh();
        });
    }

    public function deleteWorkflowStep(LetterTypeVersion $version, LetterWorkflowStep $step): void
    {
        $this->withLockedDraft($version, function () use ($version, $step): void {
            $locked = LetterWorkflowStep::query()->lockForUpdate()->findOrFail($step->id);
            $this->assertChildBelongsToVersion($locked->letter_type_version_id, $version);
            $locked->delete();
        });
    }

    private function withLockedDraft(LetterTypeVersion $version, Closure $callback): mixed
    {
        return DB::transaction(function () use ($version, $callback): mixed {
            LetterTypeDefinition::query()->whereKey($version->letter_type_id)->lockForUpdate()->firstOrFail();
            $locked = LetterTypeVersion::query()->lockForUpdate()->findOrFail($version->id);

            if ($locked->status !== LetterTypeVersionStatus::DRAFT) {
                throw ValidationException::withMessages([
                    'configuration' => 'Published configuration bersifat immutable. Buat draft version baru untuk melakukan perubahan.',
                ]);
            }

            return $callback($locked);
        }, 3);
    }

    private function assertChildBelongsToVersion(int $versionId, LetterTypeVersion $version): void
    {
        abort_unless($versionId === $version->id, 404);
    }

    private function roleForScope(string $scope): ?string
    {
        return match (LetterWorkflowActorScope::from($scope)) {
            LetterWorkflowActorScope::CITIZEN => null,
            LetterWorkflowActorScope::RT => UserRole::RT->value,
            LetterWorkflowActorScope::RW => UserRole::RW->value,
            LetterWorkflowActorScope::KELURAHAN => UserRole::KELURAHAN->value,
        };
    }

    private function prepareRequirementAttributes(array $attributes): array
    {
        $this->rejectParentRelocation($attributes);
        $attributes = $this->onlyMutable($attributes, self::REQUIREMENT_MUTABLE_ATTRIBUTES);

        if (array_key_exists('evidence_type', $attributes)
            && LetterRequirementEvidenceType::tryFrom((string) $attributes['evidence_type']) === null) {
            throw ValidationException::withMessages([
                'evidence_type' => 'Jenis evidence persyaratan tidak didukung.',
            ]);
        }

        return $attributes;
    }

    private function prepareFieldAttributes(
        array $attributes,
        ?LetterFieldDefinition $existing = null,
    ): array {
        $this->rejectParentRelocation($attributes);
        $attributes = $this->onlyMutable($attributes, self::FIELD_MUTABLE_ATTRIBUTES);
        $fieldTypeValue = $attributes['field_type'] ?? $existing?->field_type?->value;
        $fieldType = LetterFieldType::tryFrom((string) $fieldTypeValue);

        if ($fieldType === null) {
            throw ValidationException::withMessages(['field_type' => 'Tipe field tidak didukung.']);
        }

        if (($attributes['data_source'] ?? null) !== null
            && LetterFieldDataSource::tryFrom((string) $attributes['data_source']) === null) {
            throw ValidationException::withMessages(['data_source' => 'Sumber data field tidak didukung.']);
        }

        $configuration = array_key_exists('configuration', $attributes)
            ? $attributes['configuration']
            : $existing?->configuration;
        if (is_array($configuration)
            && isset($configuration['options'])
            && is_array($configuration['options'])) {
            $configuration['options'] = array_map(
                static fn (mixed $option): mixed => is_string($option) ? trim($option) : $option,
                $configuration['options'],
            );
            if (array_key_exists('configuration', $attributes)) {
                $attributes['configuration'] = $configuration;
            }
        }

        if ($fieldType === LetterFieldType::SELECT) {
            $errors = $this->validator->selectOptionErrors($configuration);
            if ($errors !== []) {
                throw ValidationException::withMessages(['configuration' => $errors]);
            }
        }

        return $attributes;
    }

    private function prepareWorkflowAttributes(array $attributes): array
    {
        $this->rejectParentRelocation($attributes);
        $attributes = $this->onlyMutable($attributes, self::WORKFLOW_MUTABLE_ATTRIBUTES);
        $action = LetterWorkflowAction::tryFrom((string) ($attributes['action'] ?? ''));
        $scope = LetterWorkflowActorScope::tryFrom((string) ($attributes['actor_scope'] ?? ''));

        if ($action === null || $scope === null) {
            throw ValidationException::withMessages([
                'workflow' => 'Action atau actor scope workflow tidak didukung.',
            ]);
        }

        if (! $this->validator->scopeSupportsAction($scope, $action)) {
            throw ValidationException::withMessages([
                'actor_scope' => 'Action tidak konsisten dengan actor scope yang dipilih.',
            ]);
        }

        $positionValue = $attributes['village_position'] ?? null;
        $position = $positionValue === null ? null : VillagePosition::tryFrom((string) $positionValue);
        if (! $this->validator->positionSupportsAction($scope, $action, $position)) {
            throw ValidationException::withMessages([
                'village_position' => 'Posisi Desa tidak konsisten dengan action workflow.',
            ]);
        }

        $attributes['actor_role'] = $this->roleForScope($scope->value);

        return $attributes;
    }

    private function rejectParentRelocation(array $attributes): void
    {
        if (array_key_exists('letter_type_version_id', $attributes)) {
            throw ValidationException::withMessages([
                'letter_type_version_id' => 'Parent configuration tidak dapat diubah.',
            ]);
        }
    }

    private function onlyMutable(array $attributes, array $allowed): array
    {
        return array_intersect_key($attributes, array_flip($allowed));
    }
}
