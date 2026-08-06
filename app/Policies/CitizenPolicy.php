<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Citizen;
use App\Models\User;

class CitizenPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, Citizen $citizen): bool
    {
        if (! $user->is_active) {
            return false;
        }

        return match ($user->role) {
            UserRole::RT => $user->rt_id === $citizen->rt_id,
            UserRole::RW => $user->rw_id !== null && $citizen->rt()->where('rw_id', $user->rw_id)->exists(),
            UserRole::ADMIN, UserRole::KELURAHAN => $user->isVillageOffice(),
        };
    }

    public function create(User $user): bool
    {
        return $user->is_active && ($user->role === UserRole::RT || $user->isSystemAdmin() || $user->isVillageSecretary());
    }

    public function update(User $user, Citizen $citizen): bool
    {
        return $this->view($user, $citizen) && ($user->role === UserRole::RT || $user->isSystemAdmin() || $user->isVillageSecretary());
    }

    public function toggleActive(User $user, Citizen $citizen): bool
    {
        return $this->update($user, $citizen);
    }
}
