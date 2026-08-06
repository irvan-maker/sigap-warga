<?php

namespace App\Http\Requests;

use App\Support\PhoneNumberNormalizer;
use Illuminate\Foundation\Http\FormRequest;

class TrackingLetterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reference' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:30'],
            'phone_normalized' => ['required', 'string', 'regex:/^62\d{8,13}$/'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'reference' => strtoupper(trim((string) $this->input('reference'))),
            'phone_normalized' => app(PhoneNumberNormalizer::class)->normalize((string) $this->input('phone')),
        ]);
    }
}
