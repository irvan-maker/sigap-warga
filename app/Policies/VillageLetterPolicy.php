<?php

namespace App\Policies;

use App\Enums\LetterStatus;
use App\Enums\UserRole;
use App\Models\User;
use App\Models\VillageLetter;

class VillageLetterPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, VillageLetter $letter): bool
    {
        if (! $user->is_active) {
            return false;
        }

return match ($user->role) {
            UserRole::RT => $user->rt_id === $letter->rt_id,UserRole::RW => $user->rw_id !== null && $letter->rt()->where('rw_id', $user->rw_id)->exists(),UserRole::ADMIN,UserRole::KELURAHAN => $user->isVillageOffice()
        };
    }

    public function create(User $user): bool
    {
        return $user->is_active && $user->role === UserRole::RT;
    }

    public function update(User $user, VillageLetter $letter): bool
    {
        return $this->view($user, $letter) && $user->role === UserRole::RT && $letter->status === LetterStatus::DRAFT;
    }

    public function submit(User $user, VillageLetter $letter): bool
    {
        return $this->update($user, $letter);
    }

    public function review(User $user, VillageLetter $letter): bool
    {
        return $this->view($user, $letter) && $user->role === UserRole::RW && $letter->status === LetterStatus::SUBMITTED;
    }

    public function approve(User $user, VillageLetter $letter): bool
    {
        return $this->villageMutator($user) && $letter->status === LetterStatus::RW_REVIEWED;
    }

    public function reject(User $user, VillageLetter $letter): bool
    {
        return $this->review($user, $letter) || ($this->villageMutator($user) && $letter->status === LetterStatus::RW_REVIEWED);
    }

    public function issue(User $user, VillageLetter $letter): bool
    {
        return $this->villageMutator($user) && $letter->status === LetterStatus::APPROVED;
    }

    public function downloadPdf(User $user, VillageLetter $letter): bool
    {
        return $this->view($user, $letter) && $letter->status === LetterStatus::ISSUED;
    }

    private function villageMutator(User $user): bool
    {
        return $user->isSystemAdmin() || $user->isVillageSecretary();
    }
}
