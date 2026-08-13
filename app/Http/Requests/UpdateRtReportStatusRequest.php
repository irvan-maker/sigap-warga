<?php

namespace App\Http\Requests;

use App\Enums\ReportStatus;
use App\Models\Report;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateRtReportStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $report = $this->route('report');

        return $report instanceof Report
            && Gate::allows('updateStatusForRt', $report);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'status' => [
                'required',
                Rule::in([
                    ReportStatus::PROCESSING->value,
                    ReportStatus::COMPLETED->value,
                    ReportStatus::REJECTED->value,
                ]),
            ],
            'note' => ['nullable', 'string', 'max:2000'],
            'public_note' => [
                Rule::requiredIf(in_array($this->input('status'), [
                    ReportStatus::COMPLETED->value,
                    ReportStatus::REJECTED->value,
                ], true)),
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'note' => trim((string) $this->input('note')) ?: null,
            'public_note' => trim((string) $this->input('public_note')) ?: null,
        ]);
    }
}
