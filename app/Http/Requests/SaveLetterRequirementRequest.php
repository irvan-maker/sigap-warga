<?php

namespace App\Http\Requests;

use App\Enums\LetterRequirementEvidenceType;
use App\Http\Requests\Concerns\NormalizesJsonInput;
use App\Models\LetterRequirement;
use App\Models\LetterTypeVersion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class SaveLetterRequirementRequest extends FormRequest
{
    use NormalizesJsonInput;

    public function authorize(): bool
    {
        $requirement = $this->route('letterRequirement');
        $version = $this->route('letterTypeVersion');

        return $requirement instanceof LetterRequirement
            ? Gate::allows('update', $requirement)
            : $version instanceof LetterTypeVersion
                && Gate::allows('create', [LetterRequirement::class, $version]);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'key' => mb_strtoupper(trim((string) $this->input('key'))),
            'label' => trim((string) $this->input('label')),
            'is_required' => $this->boolean('is_required'),
            'evidence_type' => $this->input('evidence_type') ?: LetterRequirementEvidenceType::UNCONFIGURED->value,
        ]);
        $this->normalizeJsonFields(['configuration']);
    }

    public function rules(): array
    {
        $version = $this->route('letterTypeVersion');
        $requirement = $this->route('letterRequirement');

        return [
            'key' => [
                'required',
                'string',
                'max:80',
                'regex:/^[A-Z][A-Z0-9_]*$/',
                Rule::unique('letter_requirements', 'key')
                    ->where('letter_type_version_id', $version->id)
                    ->ignore($requirement?->id),
            ],
            'label' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'is_required' => ['required', 'boolean'],
            'evidence_type' => ['required', Rule::enum(LetterRequirementEvidenceType::class)],
            'sequence' => [
                'required',
                'integer',
                'min:1',
                'max:65535',
                Rule::unique('letter_requirements', 'sequence')
                    ->where('letter_type_version_id', $version->id)
                    ->ignore($requirement?->id),
            ],
            'configuration' => ['nullable', 'array'],
        ];
    }
}
