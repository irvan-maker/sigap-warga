<?php

namespace App\Context;

/**
 * @phpstan-type TrustedEvents list<TrustedInboundEvent>
 */
final readonly class WhatsAppInboundAdapterResult
{
    /**
     * @param  list<TrustedInboundEvent>  $events
     */
    public function __construct(
        public array $events,
        public int $ignoredCount,
    ) {}
}
