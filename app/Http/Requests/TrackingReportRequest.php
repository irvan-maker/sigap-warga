<?php

namespace App\Http\Requests;

use App\Support\PhoneNumberNormalizer;
use Illuminate\Foundation\Http\FormRequest;

class TrackingReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'ticket_number' => ['required', 'string', 'regex:/^SGW-\d{4}-\d{5}$/'],
            'phone' => ['required', 'string', 'max:30'],
            'phone_normalized' => ['required', 'string', 'regex:/^62\d{8,13}$/'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'ticket_number' => strtoupper(trim((string) $this->input('ticket_number'))),
            'phone_normalized' => app(PhoneNumberNormalizer::class)
                ->normalize((string) $this->input('phone')),
        ]);
    }
}
