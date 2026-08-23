<?php

namespace App\Http\Requests;

use App\Enums\LetterWorkflowAction;
use App\Enums\LetterWorkflowActorScope;
use App\Enums\VillagePosition;
use App\Http\Requests\Concerns\NormalizesJsonInput;
use App\Models\LetterTypeVersion;
use App\Models\LetterWorkflowStep;
use App\Services\LetterTypePublishValidator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SaveLetterWorkflowStepRequest extends FormRequest
{
    use NormalizesJsonInput;

    public function authorize(): bool
    {
        $step = $this->route('letterWorkflowStep');
        $version = $this->route('letterTypeVersion');

        return $step instanceof LetterWorkflowStep
            ? Gate::allows('update', $step)
            : $version instanceof LetterTypeVersion
                && Gate::allows('create', [LetterWorkflowStep::class, $version]);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'village_position' => $this->input('village_position') ?: null,
            'is_required' => $this->boolean('is_required'),
        ]);
        $this->normalizeJsonFields(['configuration']);
    }

    public function rules(): array
    {
        $version = $this->route('letterTypeVersion');
        $step = $this->route('letterWorkflowStep');

        return [
            'sequence' => [
                'required',
                'integer',
                'min:1',
                'max:65535',
                Rule::unique('letter_workflow_steps', 'sequence')
                    ->where('letter_type_version_id', $version->id)
                    ->ignore($step?->id),
            ],
            'action' => ['required', Rule::enum(LetterWorkflowAction::class)],
            'actor_scope' => ['required', Rule::enum(LetterWorkflowActorScope::class)],
            'village_position' => ['nullable', Rule::in([
                VillagePosition::VILLAGE_SECRETARY->value,
                VillagePosition::VILLAGE_HEAD->value,
            ])],
            'is_required' => ['required', 'boolean'],
            'configuration' => ['nullable', 'array'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $action = LetterWorkflowAction::tryFrom((string) $this->input('action'));
            $scope = LetterWorkflowActorScope::tryFrom((string) $this->input('actor_scope'));

            if ($action === null || $scope === null) {
                return;
            }

            $publishValidator = app(LetterTypePublishValidator::class);
            if (! $publishValidator->scopeSupportsAction($scope, $action)) {
                $validator->errors()->add('actor_scope', 'Action tidak konsisten dengan actor scope yang dipilih.');
            }

            $position = $this->filled('village_position')
                ? VillagePosition::tryFrom((string) $this->input('village_position'))
                : null;
            if (! $publishValidator->positionSupportsAction($scope, $action, $position)) {
                $validator->errors()->add('village_position', 'Posisi Desa tidak konsisten dengan action workflow.');
            }
        });
    }
}
