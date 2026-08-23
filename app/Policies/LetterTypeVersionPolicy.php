<?php

namespace App\Policies;

use App\Models\LetterTypeDefinition;
use App\Models\LetterTypeVersion;
use App\Models\User;

class LetterTypeVersionPolicy
{
    public function view(User $user, LetterTypeVersion $version): bool
    {
        return $user->is_active && $user->isVillageOffice();
    }

    public function createDraft(User $user, LetterTypeDefinition $letterType): bool
    {
        return $this->canManage($user);
    }

    public function updateConfiguration(User $user, LetterTypeVersion $version): bool
    {
        return $this->canManage($user) && $version->isDraft();
    }

    public function publish(User $user, LetterTypeVersion $version): bool
    {
        return $this->updateConfiguration($user, $version);
    }

    public function delete(User $user, LetterTypeVersion $version): bool
    {
        return $this->updateConfiguration($user, $version)
            && ! $version->letters()->exists();
    }

    private function canManage(User $user): bool
    {
        return $user->isSystemAdmin() || $user->isVillageSecretary();
    }
}
