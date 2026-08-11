<?php

namespace App\Services;

use App\Context\CreateCitizenReportCommand;
use App\Enums\CitizenIntent;
use App\Enums\InboundRequestStatus;
use App\Enums\ServiceRouteTarget;
use App\Enums\ServiceTerritoryStatus;
use App\Enums\UrgencyLevel;
use App\Models\Report;
use App\Models\Rt;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Executes only a trusted, eligible REPORT routing decision.
 */
final class CreateCitizenReportService
{
    public function __construct(
        private readonly CreateReportRecordService $reportRecordService,
    ) {}

    public function create(CreateCitizenReportCommand $command): Report
    {
        $serviceTerritory = $this->validate($command);

        return DB::transaction(function () use ($command, $serviceTerritory): Report {
            $inboundRequest = $command->inboundRequest->newQuery()
                ->lockForUpdate()
                ->find($command->inboundRequest->getKey());

            if ($inboundRequest === null) {
                throw new DomainException('A persisted inbound request is required.');
            }

            if ($inboundRequest->report()->exists()) {
                throw new DomainException('The inbound request already produced a report.');
            }

            $inboundRequest->update([
                'status' => InboundRequestStatus::PROCESSING,
                'service_target' => ServiceRouteTarget::REPORT_SERVICE,
                'attempt_count' => $inboundRequest->attempt_count + 1,
                'processing_started_at' => now(),
                'completed_at' => null,
                'last_error_code' => null,
            ]);

            $report = $this->reportRecordService->create(
                citizen: $command->requester,
                serviceTerritory: $serviceTerritory,
                title: trim($command->title),
                description: trim($command->description),
                reportedAt: $command->reportedAt,
                inboundRequest: $inboundRequest,
            );

            $inboundRequest->update([
                'status' => InboundRequestStatus::SUCCEEDED,
                'completed_at' => now(),
            ]);

            return $report;
        }, 3);
    }

    private function validate(CreateCitizenReportCommand $command): Rt
    {
        $routing = $command->routingDecision;

        if (! $routing->canRoute()
            || $routing->target !== ServiceRouteTarget::REPORT_SERVICE
            || $routing->intent !== CitizenIntent::REPORT
            || ! in_array($routing->urgency, [UrgencyLevel::NORMAL, UrgencyLevel::HIGH], true)) {
            throw new DomainException('Only routable NORMAL or HIGH reports may use report execution.');
        }

        if (! $command->requester->exists || ! $command->requester->is_active) {
            throw new DomainException('An existing active citizen is required to create a report.');
        }

        $territory = $routing->serviceTerritoryDecision;

        if ($territory->intent !== CitizenIntent::REPORT
            || $territory->status !== ServiceTerritoryStatus::RESOLVED
            || $territory->preferredRt === null
            || $territory->preferredRt->is_active === false) {
            throw new DomainException('A resolved active service territory is required to create a report.');
        }

        if (trim($command->title) === '' || trim($command->description) === '') {
            throw new DomainException('Report title and description are required.');
        }

        if (! $command->inboundRequest->exists) {
            throw new DomainException('A persisted inbound request is required.');
        }

        return $territory->preferredRt;
    }
}
