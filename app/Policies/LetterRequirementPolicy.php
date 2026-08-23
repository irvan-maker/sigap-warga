<?php

namespace App\Policies;

use App\Models\LetterRequirement;
use App\Models\LetterTypeVersion;
use App\Models\User;

class LetterRequirementPolicy
{
    public function create(User $user, LetterTypeVersion $version): bool
    {
        return $this->canManage($user) && $version->isDraft();
    }

    public function update(User $user, LetterRequirement $requirement): bool
    {
        return $this->canManage($user) && $requirement->typeVersion->isDraft();
    }

    public function delete(User $user, LetterRequirement $requirement): bool
    {
        return $this->update($user, $requirement);
    }

    private function canManage(User $user): bool
    {
        return $user->isSystemAdmin() || $user->isVillageSecretary();
    }
}
