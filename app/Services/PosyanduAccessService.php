<?php

namespace App\Services;

use App\Enums\PosyanduStaffRole;
use App\Models\PosyanduStaffAssignment;
use App\Models\User;
use Illuminate\Support\Collection;

final class PosyanduAccessService
{
    /** @return Collection<int, PosyanduStaffAssignment> */
    public function assignmentsFor(User $user): Collection
    {
        return PosyanduStaffAssignment::query()
            ->with('site.rt.rw')
            ->where('user_id', $user->getKey())
            ->where('is_active', true)
            ->whereHas('site', fn ($site) => $site
                ->where('is_active', true)
                ->whereHas('rt', fn ($rt) => $rt
                    ->where('is_active', true)
                    ->whereHas('rw', fn ($rw) => $rw->where('is_active', true))))
            ->get();
    }

    public function assignmentFor(User $user, int $siteId): ?PosyanduStaffAssignment
    {
        return $this->assignmentsFor($user)
            ->first(fn (PosyanduStaffAssignment $assignment): bool => $assignment->posyandu_site_id === $siteId);
    }

    public function canManageSchedule(PosyanduStaffAssignment $assignment): bool
    {
        return in_array($assignment->role, [
            PosyanduStaffRole::COORDINATOR,
            PosyanduStaffRole::HEALTH_OFFICER,
        ], true);
    }
}
