<?php

namespace App\Http\Requests;

use App\Models\Rw;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class SaveManagedRwRequest extends FormRequest
{
    public function authorize(): bool
    {
        $rw = $this->route('rw');

        return $rw instanceof Rw ? Gate::allows('update', $rw) : Gate::allows('create', Rw::class);
    }

    public function rules(): array
    {
        $rw = $this->route('rw');

        return ['code' => ['required', 'string', 'max:255', Rule::unique('rws', 'code')->ignore($rw instanceof Rw ? $rw : null)], 'name' => ['required', 'string', 'max:255']];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['code' => strtoupper(trim((string) $this->input('code'))), 'name' => trim((string) $this->input('name'))]);
    }
}
