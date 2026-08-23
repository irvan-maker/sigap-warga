<?php

namespace App\Http\Requests;

use App\Models\LetterTypeDefinition;
use App\Models\LetterTypeVersion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class CreateLetterTypeVersionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $letterType = $this->route('letterType');

        return $letterType instanceof LetterTypeDefinition
            && Gate::allows('createDraft', [LetterTypeVersion::class, $letterType]);
    }

    public function rules(): array
    {
        return [];
    }
}
