<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Support\Facades\Gate;

class StoreAdminUserRequest extends SaveAdminUserRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', User::class);
    }

    protected function passwordRules(): array
    {
        return $this->requiredPasswordRules();
    }
}
