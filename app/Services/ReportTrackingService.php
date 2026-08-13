<?php

namespace App\Services;

use App\Models\Report;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReportTrackingService
{
    public function find(string $ticketNumber, string $normalizedPhone): ?Report
    {
        return Report::query()
            ->with([
                'attachments' => fn (HasMany $query) => $query->where('is_public', true),
                'histories' => function (HasMany $query): void {
                    $query
                        ->select(['id', 'report_id', 'old_status', 'new_status', 'public_note', 'created_at'])
                        ->oldest('created_at')
                        ->oldest('id');
                },
            ])
            ->where('ticket_number', $ticketNumber)
            ->whereHas(
                'citizen',
                fn (Builder $query): Builder => $query->where('phone_normalized', $normalizedPhone),
            )
            ->first();
    }
}
