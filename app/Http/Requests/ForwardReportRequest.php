<?php

namespace App\Http\Requests;

use App\Enums\ReportHandlingLevel;
use App\Models\Report;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ForwardReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        $report = $this->route('report');

        return $report instanceof Report && Gate::allows('forward', $report);
    }

    public function rules(): array
    {
        return [
            'target_level' => ['required', Rule::enum(ReportHandlingLevel::class)],
            'target_rt_id' => [
                'nullable',
                'integer',
                Rule::exists('rts', 'id')->where('is_active', true),
                Rule::requiredIf($this->input('target_level') === ReportHandlingLevel::RT->value),
            ],
            'reason' => ['required', 'string', 'max:2000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['reason' => trim((string) $this->input('reason'))]);
    }
}
