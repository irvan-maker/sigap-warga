<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Report;
use App\Models\User;

class ReportPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isVillageOffice() && $user->is_active;
    }

    public function view(User $user, Report $report): bool
    {
        if (! $user->is_active) {
            return false;
        }

        return match ($user->role) {
            UserRole::ADMIN => $user->isVillageOffice(),
            UserRole::RT => $this->belongsToUsersRt($user, $report),
            UserRole::RW => $this->belongsToUsersRw($user, $report),
            UserRole::KELURAHAN => $user->isVillageOffice(),
        };
    }

    public function create(User $user): bool
    {
        return $user->isSystemAdmin() || $user->isVillageSecretary();
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
        return $user->role === UserRole::RW
            && $this->belongsToUsersRw($user, $report);
    }

    public function viewForKelurahan(User $user, Report $report): bool
    {
        return $user->is_active && $user->role === UserRole::KELURAHAN;
    }

    private function belongsToUsersRt(User $user, Report $report): bool
    {
        return $user->is_active
            && $user->role === UserRole::RT
            && $user->rt_id !== null
            && $user->rt_id === $report->rt_id;
    }

    private function belongsToUsersRw(User $user, Report $report): bool
    {
        return $user->is_active
            && $user->rw_id !== null
            && $report->rt()
                ->where('rw_id', $user->rw_id)
                ->exists();
    }
}
