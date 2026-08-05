<?php

namespace App\Policies;

use App\Models\Rw;
use App\Models\User;

class RwPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isVillageOffice() && $user->is_active;
    }

    public function create(User $user): bool
    {
        return $this->allowed($user);
    }

    public function update(User $user, Rw $rw): bool
    {
        return $this->allowed($user);
    }

    public function toggleActive(User $user, Rw $rw): bool
    {
        return $this->allowed($user);
    }

    private function allowed(User $user): bool
    {
        return $user->isSystemAdmin() || $user->isVillageSecretary();
    }
}
