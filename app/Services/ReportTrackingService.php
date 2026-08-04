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
                'citizen:id,name,phone_normalized',
                'rt:id,code,name',
                'histories' => function (HasMany $query): void {
                    $query
                        ->select(['id', 'report_id', 'old_status', 'new_status', 'note', 'created_at'])
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
