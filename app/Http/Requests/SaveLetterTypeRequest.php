<?php

namespace App\Http\Requests;

use App\Models\LetterTypeDefinition;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class SaveLetterTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $letterType = $this->route('letterType');

        return $letterType instanceof LetterTypeDefinition
            ? Gate::allows('update', $letterType)
            : Gate::allows('create', LetterTypeDefinition::class);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => mb_strtoupper(trim((string) $this->input('code'))),
            'name' => trim((string) $this->input('name')),
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    public function rules(): array
    {
        $letterType = $this->route('letterType');

        return [
            'code' => [
                'required',
                'string',
                'max:80',
                'regex:/^[A-Z][A-Z0-9_]*$/',
                Rule::unique('letter_types', 'code')->ignore($letterType?->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.regex' => 'Kode harus diawali huruf dan hanya berisi huruf kapital, angka, atau underscore.',
            'code.unique' => 'Kode jenis surat sudah digunakan.',
        ];
    }
}
