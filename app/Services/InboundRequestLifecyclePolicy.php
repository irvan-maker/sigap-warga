<?php

namespace App\Services;

use App\Enums\InboundRequestStatus;
use DomainException;

/**
 * Defines only the inbound lifecycle transitions used by this application.
 */
final class InboundRequestLifecyclePolicy
{
    public function assertCanTransition(
        InboundRequestStatus $from,
        InboundRequestStatus $to,
    ): void {
        $allowed = match ($from) {
            InboundRequestStatus::RECEIVED => [InboundRequestStatus::PROCESSING],
            InboundRequestStatus::PROCESSING => [
                InboundRequestStatus::SUCCEEDED,
                InboundRequestStatus::BLOCKED,
                InboundRequestStatus::PENDING_ACTION,
                InboundRequestStatus::FAILED,
            ],
            InboundRequestStatus::SUCCEEDED,
            InboundRequestStatus::BLOCKED,
            InboundRequestStatus::PENDING_ACTION,
            InboundRequestStatus::FAILED => [],
        };

        if (! in_array($to, $allowed, true)) {
            throw new DomainException("Invalid inbound transition from {$from->value} to {$to->value}.");
        }
    }
}
