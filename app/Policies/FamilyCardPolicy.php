<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\FamilyCard;
use App\Models\User;

class FamilyCardPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, FamilyCard $card): bool
    {
        if (! $user->is_active) {
            return false;
        }

        return match ($user->role) {
            UserRole::RT => $user->rt_id === $card->rt_id,
            UserRole::RW => $user->rw_id !== null && $card->rt()->where('rw_id', $user->rw_id)->exists(),
            UserRole::ADMIN, UserRole::KELURAHAN => $user->isVillageOffice(),
        };
    }

    public function create(User $user): bool
    {
        return $user->is_active && ($user->role === UserRole::RT || $user->isSystemAdmin() || $user->isVillageSecretary());
    }

    public function update(User $user, FamilyCard $card): bool
    {
        return $this->view($user, $card) && ($user->role === UserRole::RT || $user->isSystemAdmin() || $user->isVillageSecretary());
    }

    public function toggleActive(User $user, FamilyCard $card): bool
    {
        return $this->update($user, $card);
    }
}
