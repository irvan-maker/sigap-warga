<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Report;
use App\Models\User;

class ReportPolicy
{
    public function create(User $user): bool
    {
        return $user->is_active && $user->role === UserRole::ADMIN;
    }

    public function viewForRt(User $user, Report $report): bool
    {
        return $this->belongsToUsersRt($user, $report);
    }

    public function updateStatusForRt(User $user, Report $report): bool
    {
        return $this->belongsToUsersRt($user, $report);
    }

    public function viewForRw(User $user, Report $report): bool
    {
        return $user->is_active
            && $user->role === UserRole::RW
            && $user->rw_id !== null
            && $report->rt()
                ->where('rw_id', $user->rw_id)
                ->exists();
    }

    private function belongsToUsersRt(User $user, Report $report): bool
    {
        return $user->is_active
            && $user->role === UserRole::RT
            && $user->rt_id !== null
            && $user->rt_id === $report->rt_id;
    }
}
