<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $administrator): bool
    {
        return $administrator->isSystemAdmin() || $administrator->isVillageSecretary();
    }

    public function create(User $administrator): bool
    {
        return $administrator->isSystemAdmin() || $administrator->isVillageSecretary();
    }

    public function update(User $administrator, User $user): bool
    {
        return $administrator->isSystemAdmin()
            || ($administrator->isVillageSecretary() && ! $user->isSystemAdmin());
    }

    public function toggleActive(User $administrator, User $user): bool
    {
        return ! $administrator->is($user) && ($administrator->isSystemAdmin()
            || ($administrator->isVillageSecretary() && ! $user->isSystemAdmin()));
    }

    public function resetPassword(User $administrator, User $user): bool
    {
        return $administrator->isSystemAdmin()
            || ($administrator->isVillageSecretary() && ! $user->isSystemAdmin());
    }
}
