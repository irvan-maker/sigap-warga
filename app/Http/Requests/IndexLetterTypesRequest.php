<?php

namespace App\Http\Requests;

use App\Models\LetterTypeDefinition;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class IndexLetterTypesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('viewAny', LetterTypeDefinition::class);
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ];
    }
}
