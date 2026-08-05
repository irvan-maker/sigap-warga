<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Rt;
use App\Models\User;

class RtPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->allowed($user);
    }

    public function create(User $user): bool
    {
        return $this->allowed($user);
    }

    public function update(User $user, Rt $rt): bool
    {
        return $this->allowed($user) && $user->rw_id === $rt->rw_id;
    }

    public function toggleActive(User $user, Rt $rt): bool
    {
        return $this->update($user, $rt);
    }

    private function allowed(User $user): bool
    {
        return $user->is_active && $user->role === UserRole::RW && $user->rw_id !== null;
    }
}
