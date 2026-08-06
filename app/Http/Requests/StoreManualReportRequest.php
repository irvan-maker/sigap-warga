<?php

namespace App\Http\Requests;

use App\Models\Report;
use App\Support\PhoneNumberNormalizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreManualReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', Report::class);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'rt_id' => [
                'required',
                'integer',
                Rule::exists('rts', 'id')->where('is_active', true),
            ],
            'citizen_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'phone_normalized' => ['required', 'string', 'regex:/^62\d{8,13}$/'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'photos' => ['nullable', 'array', 'max:3'],
            'photos.*' => ['file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'citizen_name' => trim((string) $this->input('citizen_name')),
            'phone_normalized' => app(PhoneNumberNormalizer::class)
                ->normalize((string) $this->input('phone')),
            'title' => trim((string) $this->input('title')),
            'description' => trim((string) $this->input('description')),
        ]);
    }
}
