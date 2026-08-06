<?php

namespace App\Http\Requests;

use App\Models\Rw;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class IndexManagedRwsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('viewAny', Rw::class);
    }

    public function rules(): array
    {
        return ['search' => ['nullable', 'string', 'max:255'], 'status' => ['nullable', Rule::in(['active', 'inactive'])]];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['search' => trim((string) $this->input('search')) ?: null, 'status' => $this->input('status') ?: null]);
    }
}
