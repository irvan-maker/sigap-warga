<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class ToggleAdminUserStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->route('user');

        return $user instanceof User && Gate::allows('toggleActive', $user);
    }

    public function rules(): array
    {
        return [];
    }
}
