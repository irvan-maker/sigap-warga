<?php

namespace App\Policies;

use App\Models\LetterTypeVersion;
use App\Models\LetterWorkflowStep;
use App\Models\User;

class LetterWorkflowStepPolicy
{
    public function create(User $user, LetterTypeVersion $version): bool
    {
        return $this->canManage($user) && $version->isDraft();
    }

    public function update(User $user, LetterWorkflowStep $step): bool
    {
        return $this->canManage($user) && $step->typeVersion->isDraft();
    }

    public function delete(User $user, LetterWorkflowStep $step): bool
    {
        return $this->update($user, $step);
    }

    private function canManage(User $user): bool
    {
        return $user->isSystemAdmin() || $user->isVillageSecretary();
    }
}
