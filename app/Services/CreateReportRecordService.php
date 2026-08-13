<?php

namespace App\Services;

use App\Enums\ReportCategory;
use App\Enums\ReportHandlingLevel;
use App\Enums\ReportPriority;
use App\Models\Citizen;
use App\Models\InboundRequest;
use App\Models\Report;
use App\Models\Rt;
use Carbon\CarbonImmutable;
use DateTimeInterface;

/**
 * Shared Report persistence behavior used inside caller-owned transactions.
 */
final class CreateReportRecordService
{
    public function __construct(
        private readonly TicketNumberGenerator $ticketNumberGenerator,
    ) {}

    public function create(
        Citizen $citizen,
        Rt $serviceTerritory,
        string $title,
        string $description,
        DateTimeInterface $reportedAt,
        ?InboundRequest $inboundRequest = null,
        ?Rt $entryTerritory = null,
        ReportCategory $category = ReportCategory::OTHER,
        ReportPriority $priority = ReportPriority::NORMAL,
    ): Report {
        $serviceTerritory->loadMissing('rw');
        $reportedAt = CarbonImmutable::instance($reportedAt);
        $sla = config("reports.sla.{$priority->value}", []);
        $responseMinutes = max(1, (int) ($sla['response_minutes'] ?? 120));
        $resolutionMinutes = max($responseMinutes, (int) ($sla['resolution_minutes'] ?? 4320));

        return Report::query()->create([
            'ticket_number' => $this->ticketNumberGenerator->generate(),
            'citizen_id' => $citizen->getKey(),
            'rt_id' => $serviceTerritory->getKey(),
            'title' => $title,
            'description' => $description,
            'reported_at' => $reportedAt,
            'response_due_at' => $reportedAt->addMinutes($responseMinutes),
            'resolution_due_at' => $reportedAt->addMinutes($resolutionMinutes),
            'inbound_request_id' => $inboundRequest?->getKey(),
            'entry_rt_id' => $entryTerritory?->getKey(),
            'incident_rt_id' => $serviceTerritory->getKey(),
            'category' => $category,
            'priority' => $priority,
            'current_handling_level' => ReportHandlingLevel::RT,
            'current_rt_id' => $serviceTerritory->getKey(),
            'current_rw_id' => $serviceTerritory->rw_id,
        ]);
    }
}
