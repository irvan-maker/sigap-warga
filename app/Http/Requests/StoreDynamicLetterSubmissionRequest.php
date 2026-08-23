<?php

namespace App\Http\Requests;

use App\Support\PhoneNumberNormalizer;
use Illuminate\Foundation\Http\FormRequest;

class StoreDynamicLetterSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'phone_normalized' => app(PhoneNumberNormalizer::class)
                ->normalize((string) $this->input('phone')),
        ]);
    }

    public function rules(): array
    {
        return [
            'letter_type_version_id' => ['required', 'integer'],
            'phone' => ['required', 'string', 'max:30'],
            'phone_normalized' => ['required', 'string', 'regex:/^62\d{8,13}$/'],
            'fields' => ['nullable', 'array', 'max:100'],
            'requirements' => ['nullable', 'array', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone_normalized.regex' => 'Nomor HP tidak valid.',
            'letter_type_version_id.required' => 'Version formulir tidak tersedia. Muat ulang halaman dan coba lagi.',
        ];
    }
}
