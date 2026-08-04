<?php

namespace App\Services;

use App\Enums\ReportStatus;
use App\Models\Report;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class ReportStatusService
{
    /**
     * @var array<string, list<ReportStatus>>
     */
    private const ALLOWED_TRANSITIONS = [
        ReportStatus::NEW->value => [
            ReportStatus::PROCESSING,
            ReportStatus::REJECTED,
        ],
        ReportStatus::PROCESSING->value => [
            ReportStatus::COMPLETED,
            ReportStatus::REJECTED,
        ],
        ReportStatus::COMPLETED->value => [],
        ReportStatus::REJECTED->value => [],
    ];

    /**
     * @return list<ReportStatus>
     */
    public function allowedTransitions(ReportStatus $status): array
    {
        return self::ALLOWED_TRANSITIONS[$status->value];
    }

    public function transition(
        Report $report,
        ReportStatus $newStatus,
        ?User $actor = null,
        ?string $note = null,
    ): Report {
        return DB::transaction(function () use ($report, $newStatus, $actor, $note): Report {
            $lockedReport = Report::query()
                ->lockForUpdate()
                ->findOrFail($report->getKey());

            $oldStatus = $lockedReport->status;

            if (! in_array($newStatus, $this->allowedTransitions($oldStatus), true)) {
                throw new DomainException(
                    "Report status cannot transition from {$oldStatus->value} to {$newStatus->value}.",
                );
            }

            $lockedReport->update(['status' => $newStatus]);
            $lockedReport->histories()->create([
                'user_id' => $actor?->getKey(),
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'note' => $note,
            ]);

            return $lockedReport;
        }, 3);
    }
}
