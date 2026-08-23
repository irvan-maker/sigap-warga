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
use App\Models\LetterTypeVersion;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LetterTypePublishValidator
{
    public function assertPublishable(LetterTypeVersion $version): void
    {
        $errors = [];
        $version->loadMissing('typeDefinition');

        if ($version->status !== LetterTypeVersionStatus::DRAFT) {
            $errors[] = 'Hanya configuration version berstatus draft yang dapat dipublish.';
        }

        if (! $version->typeDefinition->is_active) {
            $errors[] = 'Jenis surat harus aktif sebelum configuration dipublish.';
        }

        $requirements = DB::table('letter_requirements')
            ->where('letter_type_version_id', $version->id)
            ->orderBy('sequence')
            ->get();
        $fields = DB::table('letter_field_definitions')
            ->where('letter_type_version_id', $version->id)
            ->orderBy('sequence')
            ->get();
        $steps = DB::table('letter_workflow_steps')
            ->where('letter_type_version_id', $version->id)
            ->orderBy('sequence')
            ->get();

        $this->validateRequirements($requirements, $errors);
        $this->validateFields($fields, $errors);
        $this->validateWorkflow($steps, $errors);

        if ($errors !== []) {
            throw ValidationException::withMessages(['configuration' => $errors]);
        }
    }

    private function validateRequirements($requirements, array &$errors): void
    {
        $allowedEvidenceTypes = $this->values(LetterRequirementEvidenceType::cases());

        if ($requirements->pluck('key')->duplicates()->isNotEmpty()) {
            $errors[] = 'Key persyaratan tidak boleh duplikat.';
        }

        if ($requirements->pluck('sequence')->duplicates()->isNotEmpty()) {
            $errors[] = 'Urutan persyaratan tidak boleh duplikat.';
        }

        foreach ($requirements as $requirement) {
            if (! is_string($requirement->key)
                || mb_strlen($requirement->key) > 80
                || preg_match('/^[A-Z][A-Z0-9_]*$/', $requirement->key) !== 1) {
                $errors[] = 'Key persyaratan wajib diawali huruf dan hanya berisi huruf kapital, angka, atau underscore dengan panjang maksimal 80 karakter.';
            }

            if (! is_string($requirement->label)
                || trim($requirement->label) === ''
                || mb_strlen(trim($requirement->label)) > 255) {
                $errors[] = "Label persyaratan {$requirement->key} wajib diisi dengan panjang maksimal 255 karakter.";
            }

            if ((int) $requirement->sequence < 1) {
                $errors[] = "Urutan persyaratan {$requirement->key} harus lebih besar dari nol.";
            }

            if ($requirement->evidence_type === null
                || ! in_array($requirement->evidence_type, $allowedEvidenceTypes, true)
                || $requirement->evidence_type === LetterRequirementEvidenceType::UNCONFIGURED->value) {
                $errors[] = "Jenis bukti untuk persyaratan {$requirement->key} belum dikonfigurasi.";
            }
        }
    }

    private function validateFields($fields, array &$errors): void
    {
        $allowedFieldTypes = $this->values(LetterFieldType::cases());
        $allowedSources = $this->values(LetterFieldDataSource::cases());

        if ($fields->pluck('key')->duplicates()->isNotEmpty()) {
            $errors[] = 'Key field formulir tidak boleh duplikat.';
        }

        if ($fields->pluck('sequence')->duplicates()->isNotEmpty()) {
            $errors[] = 'Urutan field formulir tidak boleh duplikat.';
        }

        foreach ($fields as $field) {
            if (! is_string($field->key)
                || mb_strlen($field->key) > 80
                || preg_match('/^[a-z][a-z0-9_]*$/', $field->key) !== 1) {
                $errors[] = 'Key field wajib memakai snake_case huruf kecil, diawali huruf, dengan panjang maksimal 80 karakter.';
            }

            if (! is_string($field->label)
                || trim($field->label) === ''
                || mb_strlen(trim($field->label)) > 255) {
                $errors[] = "Label field {$field->key} wajib diisi dengan panjang maksimal 255 karakter.";
            }

            if ((int) $field->sequence < 1) {
                $errors[] = "Urutan field {$field->key} harus lebih besar dari nol.";
            }

            if (! in_array($field->field_type, $allowedFieldTypes, true)) {
                $errors[] = "Tipe field {$field->key} tidak didukung.";

                continue;
            }

            if ($field->data_source !== null && ! in_array($field->data_source, $allowedSources, true)) {
                $errors[] = "Sumber data field {$field->key} tidak didukung.";
            }

            if ($field->field_type === LetterFieldType::SELECT->value) {
                foreach ($this->selectOptionErrors($field->configuration) as $optionError) {
                    $errors[] = "Field pilihan {$field->key}: {$optionError}";
                }
            }
        }
    }

    private function validateWorkflow($steps, array &$errors): void
    {
        if ($steps->isEmpty()) {
            $errors[] = 'Workflow minimal harus mempunyai satu step.';

            return;
        }

        if ($steps->pluck('sequence')->duplicates()->isNotEmpty()) {
            $errors[] = 'Urutan workflow tidak boleh duplikat.';
        }

        $allowedActions = $this->values(LetterWorkflowAction::cases());
        $allowedScopes = $this->values(LetterWorkflowActorScope::cases());
        $validatedSteps = [];

        foreach ($steps as $step) {
            if ((int) $step->sequence < 1) {
                $errors[] = "Urutan workflow {$step->sequence} harus lebih besar dari nol.";
            }

            if (! in_array($step->action, $allowedActions, true)
                || ! in_array($step->actor_scope, $allowedScopes, true)) {
                $errors[] = "Action atau actor scope pada urutan {$step->sequence} tidak didukung.";

                continue;
            }

            $action = LetterWorkflowAction::from($step->action);
            $scope = LetterWorkflowActorScope::from($step->actor_scope);
            if (! $this->scopeSupportsAction($scope, $action)) {
                $errors[] = "Action {$action->value} tidak konsisten dengan actor {$scope->value}.";
            }

            $position = $step->village_position === null
                ? null
                : VillagePosition::tryFrom($step->village_position);
            if ($step->village_position !== null && $position === null) {
                $errors[] = "Posisi Desa pada urutan {$step->sequence} tidak didukung.";
            } elseif (! $this->positionSupportsAction($scope, $action, $position)) {
                $errors[] = "Posisi Desa pada urutan {$step->sequence} tidak konsisten dengan action {$action->value}.";
            }

            $expectedRole = match ($scope) {
                LetterWorkflowActorScope::CITIZEN => null,
                LetterWorkflowActorScope::RT => UserRole::RT->value,
                LetterWorkflowActorScope::RW => UserRole::RW->value,
                LetterWorkflowActorScope::KELURAHAN => UserRole::KELURAHAN->value,
            };
            if ($step->actor_role !== $expectedRole) {
                $errors[] = "Role actor pada urutan {$step->sequence} tidak konsisten dengan scope.";
            }

            if (! (bool) $step->is_required) {
                $errors[] = "Workflow step {$action->value} pada urutan {$step->sequence} wajib bersifat mandatory.";
            }

            $validatedSteps[] = [
                'sequence' => (int) $step->sequence,
                'action' => $action,
                'scope' => $scope,
            ];
        }

        $actionCounts = array_count_values(array_map(
            static fn (array $step): string => $step['action']->value,
            $validatedSteps,
        ));
        foreach ([
            LetterWorkflowAction::SUBMIT->value => 'Workflow harus mempunyai tepat satu SUBMIT.',
            LetterWorkflowAction::APPROVE->value => 'Workflow harus mempunyai tepat satu APPROVE oleh Sekretaris Desa.',
            LetterWorkflowAction::SIGN->value => 'Workflow harus mempunyai tepat satu SIGN oleh Kepala Desa.',
            LetterWorkflowAction::ISSUE->value => 'Workflow harus mempunyai tepat satu ISSUE.',
        ] as $action => $message) {
            if (($actionCounts[$action] ?? 0) !== 1) {
                $errors[] = $message;
            }
        }

        if ($steps->first()->action !== LetterWorkflowAction::SUBMIT->value) {
            $errors[] = 'SUBMIT harus menjadi step workflow pertama.';
        }

        if ($steps->last()->action !== LetterWorkflowAction::ISSUE->value) {
            $errors[] = 'ISSUE harus menjadi step workflow terakhir.';
        }

        $stageOrder = [
            LetterWorkflowAction::SUBMIT->value => 0,
            LetterWorkflowAction::VERIFY->value => 1,
            LetterWorkflowAction::APPROVE->value => 2,
            LetterWorkflowAction::SIGN->value => 3,
            LetterWorkflowAction::ISSUE->value => 4,
        ];
        $previousStage = -1;
        foreach ($validatedSteps as $step) {
            $stage = $stageOrder[$step['action']->value];
            if ($stage < $previousStage) {
                $errors[] = 'Urutan workflow wajib SUBMIT, VERIFY opsional, APPROVE, SIGN, lalu ISSUE.';

                break;
            }
            $previousStage = $stage;
        }

        $regionalVerifications = array_values(array_filter(
            $validatedSteps,
            static fn (array $step): bool => $step['action'] === LetterWorkflowAction::VERIFY,
        ));
        $rtVerifications = array_values(array_filter(
            $regionalVerifications,
            static fn (array $step): bool => $step['scope'] === LetterWorkflowActorScope::RT,
        ));
        $rwVerifications = array_values(array_filter(
            $regionalVerifications,
            static fn (array $step): bool => $step['scope'] === LetterWorkflowActorScope::RW,
        ));

        if (count($rtVerifications) > 1) {
            $errors[] = 'Workflow hanya boleh mempunyai satu verifikasi RT.';
        }
        if (count($rwVerifications) > 1) {
            $errors[] = 'Workflow hanya boleh mempunyai satu verifikasi RW.';
        }
        if ($rwVerifications !== [] && $rtVerifications === []) {
            $errors[] = 'Verifikasi RW hanya dapat dikonfigurasi jika verifikasi RT juga ada.';
        }
        if ($rtVerifications !== [] && $rwVerifications !== []
            && $rtVerifications[0]['sequence'] >= $rwVerifications[0]['sequence']) {
            $errors[] = 'Verifikasi RT harus terjadi sebelum verifikasi RW.';
        }
    }

    public function scopeSupportsAction(
        LetterWorkflowActorScope $scope,
        LetterWorkflowAction $action,
    ): bool {
        return match ($action) {
            LetterWorkflowAction::SUBMIT => $scope === LetterWorkflowActorScope::CITIZEN,
            LetterWorkflowAction::VERIFY => in_array($scope, [
                LetterWorkflowActorScope::RT,
                LetterWorkflowActorScope::RW,
            ], true),
            LetterWorkflowAction::APPROVE,
            LetterWorkflowAction::SIGN,
            LetterWorkflowAction::ISSUE => $scope === LetterWorkflowActorScope::KELURAHAN,
        };
    }

    public function positionSupportsAction(
        LetterWorkflowActorScope $scope,
        LetterWorkflowAction $action,
        ?VillagePosition $position,
    ): bool {
        return match ($action) {
            LetterWorkflowAction::SUBMIT => $scope === LetterWorkflowActorScope::CITIZEN
                && $position === null,
            LetterWorkflowAction::VERIFY => in_array($scope, [
                LetterWorkflowActorScope::RT,
                LetterWorkflowActorScope::RW,
            ], true) && $position === null,
            LetterWorkflowAction::APPROVE => $scope === LetterWorkflowActorScope::KELURAHAN
                && $position === VillagePosition::VILLAGE_SECRETARY,
            LetterWorkflowAction::SIGN => $scope === LetterWorkflowActorScope::KELURAHAN
                && $position === VillagePosition::VILLAGE_HEAD,
            // NEEDS CONFIRMATION: Phase 2 uses the Secretary as the safe administrative issuer.
            LetterWorkflowAction::ISSUE => $scope === LetterWorkflowActorScope::KELURAHAN
                && $position === VillagePosition::VILLAGE_SECRETARY,
        };
    }

    /** @return array<int, string> */
    public function selectOptionErrors(mixed $value): array
    {
        $configuration = $this->jsonObject($value);
        if (! array_key_exists('options', $configuration)) {
            return ['configuration.options wajib diisi.'];
        }

        $options = $configuration['options'];
        if (! is_array($options) || ! array_is_list($options) || $options === []) {
            return ['configuration.options wajib berupa array non-empty berisi string.'];
        }

        $errors = [];
        $normalized = [];
        foreach ($options as $index => $option) {
            if (! is_string($option)) {
                $errors[] = "configuration.options.{$index} wajib berupa string.";

                continue;
            }

            $option = trim($option);
            if ($option === '') {
                $errors[] = "configuration.options.{$index} tidak boleh kosong.";

                continue;
            }

            if (in_array($option, $normalized, true)) {
                $errors[] = "configuration.options mengandung nilai duplikat: {$option}.";

                continue;
            }

            $normalized[] = $option;
        }

        return $errors;
    }

    private function values(array $cases): array
    {
        return array_map(static fn ($case): string => $case->value, $cases);
    }

    private function jsonObject(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value)) {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }
}
