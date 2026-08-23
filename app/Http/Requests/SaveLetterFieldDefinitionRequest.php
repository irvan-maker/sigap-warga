<?php

namespace App\Http\Requests;

use App\Enums\LetterFieldDataSource;
use App\Enums\LetterFieldType;
use App\Http\Requests\Concerns\NormalizesJsonInput;
use App\Models\LetterFieldDefinition;
use App\Models\LetterTypeVersion;
use App\Services\LetterTypePublishValidator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SaveLetterFieldDefinitionRequest extends FormRequest
{
    use NormalizesJsonInput;

    public function authorize(): bool
    {
        $field = $this->route('letterFieldDefinition');
        $version = $this->route('letterTypeVersion');

        return $field instanceof LetterFieldDefinition
            ? Gate::allows('update', $field)
            : $version instanceof LetterTypeVersion
                && Gate::allows('create', [LetterFieldDefinition::class, $version]);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'key' => mb_strtolower(trim((string) $this->input('key'))),
            'label' => trim((string) $this->input('label')),
            'is_required' => $this->boolean('is_required'),
            'data_source' => $this->input('data_source') ?: null,
        ]);
        $this->normalizeJsonFields(['validation', 'configuration']);

        $configuration = $this->input('configuration');
        if (is_array($configuration)
            && isset($configuration['options'])
            && is_array($configuration['options'])) {
            $configuration['options'] = array_map(
                static fn (mixed $option): mixed => is_string($option) ? trim($option) : $option,
                $configuration['options'],
            );
            $this->merge(['configuration' => $configuration]);
        }
    }

    public function rules(): array
    {
        $version = $this->route('letterTypeVersion');
        $field = $this->route('letterFieldDefinition');

        return [
            'key' => [
                'required',
                'string',
                'max:80',
                'regex:/^[a-z][a-z0-9_]*$/',
                Rule::unique('letter_field_definitions', 'key')
                    ->where('letter_type_version_id', $version->id)
                    ->ignore($field?->id),
            ],
            'label' => ['required', 'string', 'max:255'],
            'field_type' => ['required', Rule::enum(LetterFieldType::class)],
            'is_required' => ['required', 'boolean'],
            'sequence' => [
                'required',
                'integer',
                'min:1',
                'max:65535',
                Rule::unique('letter_field_definitions', 'sequence')
                    ->where('letter_type_version_id', $version->id)
                    ->ignore($field?->id),
            ],
            'data_source' => ['nullable', Rule::enum(LetterFieldDataSource::class)],
            'validation' => ['nullable', 'array'],
            'configuration' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'field_type' => 'Tipe field tidak didukung.',
            'key.regex' => 'Key field harus memakai snake_case huruf kecil.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (LetterFieldType::tryFrom((string) $this->input('field_type')) !== LetterFieldType::SELECT) {
                return;
            }

            foreach (app(LetterTypePublishValidator::class)->selectOptionErrors($this->input('configuration')) as $error) {
                $validator->errors()->add('configuration', $error);
            }
        });
    }
}
