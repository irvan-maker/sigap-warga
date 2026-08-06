<?php

namespace App\Services;

use App\Enums\LetterStatus;
use App\Models\User;
use App\Models\VillageLetter;
use DomainException;
use Illuminate\Support\Facades\DB;

class VillageLetterWorkflow
{
    public function __construct(private readonly LetterNumberService $numbers) {}

    public function transition(VillageLetter $letter, LetterStatus $to, User $actor, ?string $note = null): VillageLetter
    {
        return DB::transaction(function () use ($letter, $to, $actor, $note) {
            $locked = VillageLetter::query()->lockForUpdate()->findOrFail($letter->id);
            $from = $locked->status;
            $allowed = match ($from) {
                LetterStatus::DRAFT => [LetterStatus::SUBMITTED],LetterStatus::SUBMITTED => [LetterStatus::RW_REVIEWED, LetterStatus::REJECTED],LetterStatus::RW_REVIEWED => [LetterStatus::APPROVED, LetterStatus::REJECTED],LetterStatus::APPROVED => [LetterStatus::ISSUED],default => []
            };
            if (! in_array($to, $allowed, true)) {
                throw new DomainException("Transisi status {$from->value} ke {$to->value} tidak valid.");
            } if ($to === LetterStatus::REJECTED && blank($note)) {
                throw new DomainException('Alasan penolakan wajib diisi.');
            } $data = ['status' => $to];
            if ($to === LetterStatus::SUBMITTED) {
                $data['submitted_at'] = now();
            } if ($to === LetterStatus::RW_REVIEWED) {
                $data += ['reviewed_at' => now(), 'reviewed_by_rw' => $actor->id];
            } if ($to === LetterStatus::APPROVED) {
                $data += ['approved_at' => now(), 'approved_by_village' => $actor->id];
            } if ($to === LetterStatus::ISSUED) {
                $data += ['issued_at' => now(), 'letter_number' => $this->numbers->issue($locked)];
            } $locked->update($data);
            $locked->histories()->create(['user_id' => $actor->id, 'old_status' => $from, 'new_status' => $to, 'note' => $note]);

            return $locked;
        }, 3);
    }
}
