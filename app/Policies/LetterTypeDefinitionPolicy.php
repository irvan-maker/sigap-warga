<?php

namespace App\Policies;

use App\Models\LetterTypeDefinition;
use App\Models\User;

class LetterTypeDefinitionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active && $user->isVillageOffice();
    }

    public function view(User $user, LetterTypeDefinition $letterType): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->canManage($user);
    }

    public function update(User $user, LetterTypeDefinition $letterType): bool
    {
        return $this->canManage($user);
    }

    public function toggleActive(User $user, LetterTypeDefinition $letterType): bool
    {
        return $this->canManage($user);
    }

    private function canManage(User $user): bool
    {
        return $user->isSystemAdmin() || $user->isVillageSecretary();
    }
}
