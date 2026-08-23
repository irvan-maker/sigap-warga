<?php

namespace App\Policies;

use App\Models\LetterFieldDefinition;
use App\Models\LetterTypeVersion;
use App\Models\User;

class LetterFieldDefinitionPolicy
{
    public function create(User $user, LetterTypeVersion $version): bool
    {
        return $this->canManage($user) && $version->isDraft();
    }

    public function update(User $user, LetterFieldDefinition $field): bool
    {
        return $this->canManage($user) && $field->typeVersion->isDraft();
    }

    public function delete(User $user, LetterFieldDefinition $field): bool
    {
        return $this->update($user, $field);
    }

    private function canManage(User $user): bool
    {
        return $user->isSystemAdmin() || $user->isVillageSecretary();
    }
}
