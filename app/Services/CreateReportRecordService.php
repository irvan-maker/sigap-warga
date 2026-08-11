<?php

namespace App\Services;

use App\Models\Citizen;
use App\Models\InboundRequest;
use App\Models\Report;
use App\Models\Rt;
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
    ): Report {
        return Report::query()->create([
            'ticket_number' => $this->ticketNumberGenerator->generate(),
            'citizen_id' => $citizen->getKey(),
            'rt_id' => $serviceTerritory->getKey(),
            'title' => $title,
            'description' => $description,
            'reported_at' => $reportedAt,
            'inbound_request_id' => $inboundRequest?->getKey(),
        ]);
    }
}
