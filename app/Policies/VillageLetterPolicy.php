<?php

namespace App\Policies;

use App\Enums\LetterApprovalLevel;
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
            UserRole::RT => $user->rt_id === $letter->rt_id,

            UserRole::RW => $user->rw_id !== null
                && $letter->rt()
                    ->where('rw_id', $user->rw_id)
                    ->exists(),

            UserRole::ADMIN,
            UserRole::KELURAHAN => $user->isVillageOffice(),
        };
    }

    public function create(User $user): bool
    {
        return $user->is_active
            && $user->role === UserRole::RT;
    }

    public function update(
        User $user,
        VillageLetter $letter,
    ): bool {
        return ! $letter->isGenericSubmission()
            && $this->view($user, $letter)
            && $user->role === UserRole::RT
            && $letter->status === LetterStatus::DRAFT;
    }

    public function submit(
        User $user,
        VillageLetter $letter,
    ): bool {
        return $this->update($user, $letter);
    }

    public function review(
        User $user,
        VillageLetter $letter,
    ): bool {
        return ! $letter->isGenericSubmission()
            && $this->view($user, $letter)
            && $user->role === UserRole::RW
            && $letter->status === LetterStatus::SUBMITTED
            && in_array(
                $letter->required_approval_level,
                [
                    LetterApprovalLevel::RW,
                    LetterApprovalLevel::KELURAHAN,
                ],
                true,
            );
    }

    public function approve(
        User $user,
        VillageLetter $letter,
    ): bool {
        if ($letter->isGenericSubmission()) {
            return $this->view($user, $letter)
                && $user->isVillageSecretary()
                && $letter->status === LetterStatus::SUBMITTED
                && $this->genericNextAction($letter) === 'APPROVE';
        }

        return $letter->required_approval_level
                === LetterApprovalLevel::KELURAHAN
            && $this->villageMutator($user)
            && $letter->status === LetterStatus::RW_REVIEWED;
    }

    public function sign(
        User $user,
        VillageLetter $letter,
    ): bool {
        return $letter->isGenericSubmission()
            && $this->view($user, $letter)
            && $user->isVillageHead()
            && $letter->status === LetterStatus::APPROVED
            && $this->genericNextAction($letter) === 'SIGN';
    }

    public function reject(
        User $user,
        VillageLetter $letter,
    ): bool {
        /*
         * Untuk MVP finalisasi KKN, penolakan generic belum dibuka.
         * Jalur legacy tetap tidak berubah.
         */
        return ! $letter->isGenericSubmission()
            && (
                $this->review($user, $letter)
                || (
                    $letter->required_approval_level
                        === LetterApprovalLevel::KELURAHAN
                    && $this->villageMutator($user)
                    && $letter->status === LetterStatus::RW_REVIEWED
                )
            );
    }

    public function issue(
        User $user,
        VillageLetter $letter,
    ): bool {
        if ($letter->isGenericSubmission()) {
            return $this->view($user, $letter)
                && $user->isVillageSecretary()
                && $letter->status === LetterStatus::SIGNED
                && $this->genericNextAction($letter) === 'ISSUE';
        }

        if (
            ! $this->view($user, $letter)
            || $letter->status !== LetterStatus::APPROVED
        ) {
            return false;
        }

        return match ($letter->required_approval_level) {
            LetterApprovalLevel::RT =>
                $user->role === UserRole::RT
                && $user->rt_id === $letter->rt_id,

            LetterApprovalLevel::RW =>
                $user->role === UserRole::RW,

            LetterApprovalLevel::KELURAHAN =>
                $this->villageMutator($user),
        };
    }

    public function downloadPdf(
        User $user,
        VillageLetter $letter,
    ): bool {
        return $this->view($user, $letter)
            && $letter->status === LetterStatus::ISSUED;
    }

    private function genericNextAction(
        VillageLetter $letter,
    ): ?string {
        /*
         * Ambil submission secara eksplisit agar policy tidak
         * bergantung pada implicit lazy loading.
         */
        $submission = $letter->relationLoaded('submission')
            ? $letter->submission
            : $letter->submission()->first();

        $workflow = collect(
            $submission?->configuration_snapshot['workflow'] ?? []
        )
            ->sortBy('sequence')
            ->values();

        $currentAction = match ($letter->status) {
            LetterStatus::SUBMITTED => 'SUBMIT',
            LetterStatus::APPROVED => 'APPROVE',
            LetterStatus::SIGNED => 'SIGN',
            LetterStatus::ISSUED => 'ISSUE',
            default => null,
        };

        if ($currentAction === null) {
            return null;
        }

        $index = $workflow->search(
            fn (array $step): bool =>
                ($step['action'] ?? null) === $currentAction
        );

        if ($index === false) {
            return null;
        }

        return $workflow->get($index + 1)['action'] ?? null;
    }

    private function villageMutator(User $user): bool
    {
        return $user->isSystemAdmin()
            || $user->isVillageSecretary();
    }
}