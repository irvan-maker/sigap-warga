<?php

namespace App\Policies;

use App\Enums\ReportDispositionStatus;
use App\Enums\ReportHandlingLevel;
use App\Enums\ReportStatus;
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
        return $this->canManage($user, $report);
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

    public function forward(User $user, Report $report): bool
    {
        return $this->canManage($user, $report)
            && in_array($report->status, [ReportStatus::NEW, ReportStatus::PROCESSING], true)
            && in_array($user->role, [UserRole::RT, UserRole::RW], true);
    }

    public function acknowledge(User $user, Report $report): bool
    {
        return $this->canManage($user, $report)
            && $report->status === ReportStatus::FORWARDED
            && $report->dispositions()
                ->where('status', ReportDispositionStatus::PENDING)
                ->exists();
    }

    public function manage(User $user, Report $report): bool
    {
        return $this->canManage($user, $report);
    }

    private function belongsToUsersRt(User $user, Report $report): bool
    {
        return $user->is_active
            && $user->role === UserRole::RT
            && $user->rt_id !== null
            && (
                $user->rt_id === $report->rt_id
                || $user->rt_id === $report->incident_rt_id
                || $user->rt_id === $report->current_rt_id
                || $report->dispositions()
                    ->where(fn ($query) => $query
                        ->where('from_rt_id', $user->rt_id)
                        ->orWhere('to_rt_id', $user->rt_id))
                    ->exists()
            );
    }

    private function belongsToUsersRw(User $user, Report $report): bool
    {
        return $user->is_active
            && $user->rw_id !== null
            && (
                $report->current_rw_id === $user->rw_id
                || $report->rt()->where('rw_id', $user->rw_id)->exists()
                || $report->incidentRt()->where('rw_id', $user->rw_id)->exists()
                || $report->dispositions()
                    ->where(fn ($query) => $query
                        ->where('from_rw_id', $user->rw_id)
                        ->orWhere('to_rw_id', $user->rw_id))
                    ->exists()
            );
    }

    private function canManage(User $user, Report $report): bool
    {
        if (! $user->is_active) {
            return false;
        }

        return match ($user->role) {
            UserRole::RT => $report->current_handling_level === ReportHandlingLevel::RT
                && $report->current_rt_id === $user->rt_id,
            UserRole::RW => $report->current_handling_level === ReportHandlingLevel::RW
                && $report->current_rw_id === $user->rw_id,
            UserRole::KELURAHAN => $report->current_handling_level === ReportHandlingLevel::KELURAHAN,
            UserRole::ADMIN => false,
        };
    }
}
