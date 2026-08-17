<?php

namespace App\Http\Requests;

use App\Models\Rt;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class SaveManagedRtRequest extends FormRequest
{
    public function authorize(): bool
    {
        $rt = $this->route('rt');

        return $rt instanceof Rt ? Gate::allows('update', $rt) : Gate::allows('create', Rt::class);
    }

    public function rules(): array
    {
        $rt = $this->route('rt');

        return [
            'code' => ['required', 'string', 'max:255', Rule::unique('rts', 'code')->where('rw_id', $this->user()->rw_id)->ignore($rt instanceof Rt ? $rt : null)],
            'name' => ['required', 'string', 'max:255'],
            'whatsapp_number' => ['nullable', 'string', 'max:30', 'required_if:report_notification_enabled,1'],
            'report_notification_enabled' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => strtoupper(trim((string) $this->input('code'))),
            'name' => trim((string) $this->input('name')),
            'whatsapp_number' => trim((string) $this->input('whatsapp_number')) ?: null,
            'report_notification_enabled' => $this->boolean('report_notification_enabled'),
        ]);
    }
}
