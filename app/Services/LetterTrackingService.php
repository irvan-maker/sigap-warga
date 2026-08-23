<?php

namespace App\Services;

use App\Models\VillageLetter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LetterTrackingService
{
    public function __construct(private readonly ReporterPhoneHasher $phoneHasher) {}

    public function find(string $reference, string $normalizedPhone): ?VillageLetter
    {
        $phoneHash = $this->phoneHasher->hash($normalizedPhone);

        return VillageLetter::query()
            ->select(['id', 'public_tracking_code', 'letter_number', 'letter_type', 'letter_type_id', 'letter_type_version_id', 'citizen_id', 'status', 'created_at', 'issued_at'])
            ->with(['submission.requirements', 'histories' => function (HasMany $query): void {
                $query->select(['id', 'village_letter_id', 'new_status', 'created_at'])->oldest('created_at')->oldest('id');
            }])
            ->where(fn (Builder $query) => $query->where('public_tracking_code', $reference)->orWhere('letter_number', $reference))
            ->where(fn (Builder $query) => $query
                ->where(fn (Builder $legacy) => $legacy
                    ->whereNotNull('letter_type')
                    ->whereHas('citizen', fn (Builder $citizen) => $citizen->where('phone_normalized', $normalizedPhone)))
                ->orWhere(fn (Builder $generic) => $generic
                    ->whereNull('letter_type')
                    ->whereNotNull('letter_type_id')
                    ->whereNotNull('letter_type_version_id')
                    ->whereHas('submission', fn (Builder $submission) => $submission->where('applicant_phone_hash', $phoneHash))))
            ->first();
    }
}
