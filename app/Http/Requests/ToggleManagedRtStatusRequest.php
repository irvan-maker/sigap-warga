<?php

namespace App\Http\Requests;

use App\Models\Rt;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class ToggleManagedRtStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $rt = $this->route('rt');

        return $rt instanceof Rt && Gate::allows('toggleActive', $rt);
    }

    public function rules(): array
    {
        return [];
    }
}
