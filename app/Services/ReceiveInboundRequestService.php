<?php

namespace App\Services;

use App\Enums\InboundRequestStatus;
use App\Models\InboundRequest;
use DateTimeInterface;
use DomainException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;

/**
 * Persists channel-neutral event identity without retaining message payloads.
 */
final class ReceiveInboundRequestService
{
    public function receive(
        string $source,
        string $externalEventId,
        ?DateTimeInterface $receivedAt = null,
    ): InboundRequest {
        $source = trim($source);

        if ($source === '' || mb_strlen($source) > 64) {
            throw new DomainException('Inbound request source must contain at most 64 characters.');
        }

        if (trim($externalEventId) === '' || mb_strlen($externalEventId) > 191) {
            throw new DomainException('External event ID must contain at most 191 characters.');
        }

        try {
            return InboundRequest::query()->create([
                'source' => $source,
                'external_event_id' => $externalEventId,
                'correlation_id' => (string) Str::uuid(),
                'status' => InboundRequestStatus::RECEIVED,
                'received_at' => $receivedAt ?? now(),
            ]);
        } catch (QueryException $exception) {
            $existing = InboundRequest::query()
                ->where('source', $source)
                ->where('external_event_id', $externalEventId)
                ->first();

            if ($existing !== null) {
                return $existing;
            }

            throw $exception;
        }
    }
}
