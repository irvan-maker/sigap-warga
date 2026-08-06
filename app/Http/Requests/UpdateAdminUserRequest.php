<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Validator;

class UpdateAdminUserRequest extends SaveAdminUserRequest
{
    public function authorize(): bool
    {
        return $this->targetUser() !== null
            && Gate::allows('update', $this->targetUser());
    }

    protected function passwordRules(): array
    {
        return ['prohibited'];
    }

    protected function validateAdditionalRules(Validator $validator, ?UserRole $role): void
    {
        if ($this->targetUser()?->is($this->user()) && $role !== UserRole::ADMIN) {
            $validator->errors()->add(
                'role',
                'Administrator tidak dapat mengubah role akun sendiri.',
            );
        }
    }
}
