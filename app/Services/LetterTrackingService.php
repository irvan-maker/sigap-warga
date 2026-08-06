<?php

namespace App\Services;

use App\Models\VillageLetter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LetterTrackingService
{
    public function find(string $reference, string $normalizedPhone): ?VillageLetter
    {
        return VillageLetter::query()
            ->select(['id', 'public_tracking_code', 'letter_number', 'letter_type', 'citizen_id', 'status', 'created_at', 'issued_at'])
            ->with(['histories' => function (HasMany $query): void {
                $query->select(['id', 'village_letter_id', 'new_status', 'created_at'])->oldest('created_at')->oldest('id');
            }])
            ->where(fn (Builder $query) => $query->where('public_tracking_code', $reference)->orWhere('letter_number', $reference))
            ->whereHas('citizen', fn (Builder $query) => $query->where('phone_normalized', $normalizedPhone))
            ->first();
    }
}
