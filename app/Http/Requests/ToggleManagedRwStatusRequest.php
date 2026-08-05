<?php

namespace App\Http\Requests;

use App\Models\Rw;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class ToggleManagedRwStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $rw = $this->route('rw');

        return $rw instanceof Rw && Gate::allows('toggleActive', $rw);
    }

    public function rules(): array
    {
        return [];
    }
}
