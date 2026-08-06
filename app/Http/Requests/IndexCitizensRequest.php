<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexCitizensRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'completeness' => ['nullable', Rule::in(['without_family_card', 'without_nik'])],
            'rt_id' => ['nullable', 'integer'],
            'rw_id' => ['nullable', 'integer'],
        ];
    }
}
