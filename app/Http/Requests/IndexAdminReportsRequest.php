<?php

namespace App\Http\Requests;

use App\Enums\ReportStatus;
use App\Models\Report;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class IndexAdminReportsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('viewAny', Report::class);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::enum(ReportStatus::class)],
            'rw_id' => ['nullable', 'integer', Rule::exists('rws', 'id')],
            'rt_id' => ['nullable', 'integer', Rule::exists('rts', 'id')],
            'date_from' => [
                'nullable',
                'date_format:Y-m-d',
                Rule::when($this->filled('date_to'), ['before_or_equal:date_to']),
            ],
            'date_to' => [
                'nullable',
                'date_format:Y-m-d',
                Rule::when($this->filled('date_from'), ['after_or_equal:date_from']),
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'search' => trim((string) $this->input('search')) ?: null,
            'status' => $this->input('status') ?: null,
            'rw_id' => $this->input('rw_id') ?: null,
            'rt_id' => $this->input('rt_id') ?: null,
            'date_from' => $this->input('date_from') ?: null,
            'date_to' => $this->input('date_to') ?: null,
        ]);
    }
}
