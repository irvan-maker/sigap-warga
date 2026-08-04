<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;

class ReportPolicy
{
    public function create(User $user): bool
    {
        return $user->is_active && $user->role === UserRole::ADMIN;
    }
}
