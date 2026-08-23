<?php

namespace App\Services;

use App\Enums\LetterApprovalLevel;
use App\Enums\LetterStatus;
use App\Enums\UserRole;
use App\Models\User;
use App\Models\VillageLetter;
use DomainException;
use Illuminate\Support\Facades\DB;

class VillageLetterWorkflow
{
    public function __construct(private readonly LetterNumberService $numbers) {}

    public function transition(
        VillageLetter $letter,
        LetterStatus $requestedStatus,
        User $actor,
        ?string $note = null,
    ): VillageLetter {
        return DB::transaction(function () use ($letter, $requestedStatus, $actor, $note): VillageLetter {
            $locked = VillageLetter::query()->with('rt')->lockForUpdate()->findOrFail($letter->id);
            if ($locked->isGenericSubmission()) {
                throw new DomainException('Workflow pengajuan surat generik belum dijalankan pada Phase 3.');
            }
            $from = $locked->status;
            $this->assertActorMayTransition($locked, $requestedStatus, $actor);

            $to = $this->effectiveStatus($locked, $requestedStatus);
            $allowed = match ($from) {
                LetterStatus::DRAFT => [LetterStatus::SUBMITTED, LetterStatus::APPROVED],
                LetterStatus::SUBMITTED => [LetterStatus::RW_REVIEWED, LetterStatus::APPROVED, LetterStatus::REJECTED],
                LetterStatus::RW_REVIEWED => [LetterStatus::APPROVED, LetterStatus::REJECTED],
                LetterStatus::APPROVED => [LetterStatus::ISSUED],
                default => [],
            };

            if (! in_array($to, $allowed, true)) {
                throw new DomainException("Transisi status {$from->value} ke {$to->value} tidak valid.");
            }

            if ($to === LetterStatus::REJECTED && blank($note)) {
                throw new DomainException('Alasan penolakan wajib diisi.');
            }

            $data = ['status' => $to];

            if ($from === LetterStatus::DRAFT) {
                $data['submitted_at'] = now();
            }

            if ($actor->role === UserRole::RW
                && in_array($to, [LetterStatus::RW_REVIEWED, LetterStatus::APPROVED], true)) {
                $data += [
                    'reviewed_at' => now(),
                    'reviewed_by_rw' => $actor->id,
                ];
            }

            if ($to === LetterStatus::APPROVED) {
                $data += [
                    'approved_at' => now(),
                    'approved_by_user_id' => $actor->id,
                ];

                if ($actor->isVillageOffice()) {
                    $data['approved_by_village'] = $actor->id;
                }
            }

            if ($to === LetterStatus::ISSUED) {
                $data += [
                    'issued_at' => now(),
                    'letter_number' => $this->numbers->issue($locked),
                ];
            }

            $locked->update($data);
            $locked->histories()->create([
                'user_id' => $actor->id,
                'old_status' => $from,
                'new_status' => $to,
                'note' => $note,
            ]);

            return $locked;
        }, 3);
    }

    private function effectiveStatus(VillageLetter $letter, LetterStatus $requested): LetterStatus
    {
        if ($letter->status === LetterStatus::DRAFT
            && $requested === LetterStatus::SUBMITTED
            && $letter->required_approval_level === LetterApprovalLevel::RT) {
            return LetterStatus::APPROVED;
        }

        if ($letter->status === LetterStatus::SUBMITTED
            && $requested === LetterStatus::RW_REVIEWED
            && $letter->required_approval_level === LetterApprovalLevel::RW) {
            return LetterStatus::APPROVED;
        }

        return $requested;
    }

    private function assertActorMayTransition(
        VillageLetter $letter,
        LetterStatus $requested,
        User $actor,
    ): void {
        $allowed = $actor->is_active && match ($letter->status) {
            LetterStatus::DRAFT => $requested === LetterStatus::SUBMITTED
                && $actor->role === UserRole::RT
                && $actor->rt_id === $letter->rt_id,
            LetterStatus::SUBMITTED => in_array($requested, [LetterStatus::RW_REVIEWED, LetterStatus::REJECTED], true)
                && $actor->role === UserRole::RW
                && $actor->rw_id === $letter->rt->rw_id,
            LetterStatus::RW_REVIEWED => in_array($requested, [LetterStatus::APPROVED, LetterStatus::REJECTED], true)
                && ($actor->isSystemAdmin() || $actor->isVillageSecretary()),
            LetterStatus::APPROVED => $requested === LetterStatus::ISSUED
                && $this->isRequiredIssuer($letter, $actor),
            default => false,
        };

        if (! $allowed) {
            throw new DomainException('Petugas tidak berwenang melakukan transisi surat ini.');
        }
    }

    private function isRequiredIssuer(VillageLetter $letter, User $actor): bool
    {
        return match ($letter->required_approval_level) {
            LetterApprovalLevel::RT => $actor->role === UserRole::RT
                && $actor->rt_id === $letter->rt_id,
            LetterApprovalLevel::RW => $actor->role === UserRole::RW
                && $actor->rw_id === $letter->rt->rw_id,
            LetterApprovalLevel::KELURAHAN => $actor->isSystemAdmin() || $actor->isVillageSecretary(),
        };
    }
}
