<?php

namespace App\Services;

use App\Models\Report;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReportTrackingService
{
    public function __construct(
        private readonly ReporterPhoneHasher $reporterPhoneHasher,
    ) {}

    public function find(string $ticketNumber, string $normalizedPhone): ?Report
    {
        $phoneHash = $this->reporterPhoneHasher->hash($normalizedPhone);

        return Report::query()
            ->with([
                'rt:id,rw_id,code,name',
                'rt.rw:id,code,name',
                'attachments' => fn (HasMany $query) => $query->where('is_public', true),
                'histories' => function (HasMany $query): void {
                    $query
                        ->select(['id', 'report_id', 'old_status', 'new_status', 'public_note', 'created_at'])
                        ->oldest('created_at')
                        ->oldest('id');
                },
            ])
            ->where('ticket_number', $ticketNumber)
            ->where(function (Builder $query) use ($normalizedPhone, $phoneHash): void {
                $query
                    ->whereHas(
                        'citizen',
                        fn (Builder $citizen): Builder => $citizen->where('phone_normalized', $normalizedPhone),
                    )
                    ->orWhereHas(
                        'inboundRequest',
                        fn (Builder $inbound): Builder => $inbound->where('sender_phone_hash', $phoneHash),
                    );
            })
            ->first();
    }
}
