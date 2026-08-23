<?php

namespace App\Http\Requests;

use App\Models\LetterTypeVersion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class DeleteLetterTypeVersionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $version = $this->route('letterTypeVersion');

        return $version instanceof LetterTypeVersion && Gate::allows('delete', $version);
    }

    public function rules(): array
    {
        return [];
    }
}
