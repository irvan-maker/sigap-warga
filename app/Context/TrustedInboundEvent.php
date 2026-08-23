<?php

namespace App\Context;

use App\Enums\InboundSource;
use App\Models\Rt;
use DateTimeImmutable;

/**
 * Immutable facts emitted only by a trusted channel adapter.
 *
 * Raw channel request -> future authentication/signature verification ->
 * trusted adapter -> this contract. It must never contain provider payloads,
 * credentials, signatures, tokens, or arbitrary provider metadata.
 */
final readonly class TrustedInboundEvent
{
    public function __construct(
        public InboundSource $source,
        public string $externalEventId,
        public string $senderPhone,
        public string $message,
        public DateTimeImmutable $receivedAt,
        public ?Rt $entryRt = null,
        public ?Rt $incidentRt = null,
        public ?string $sourceNamespace = null,
        public ?string $handoffToken = null,
        public ?string $claimedEntryRtCode = null,
        public ?string $claimedEntryRwCode = null,
    ) {}

    public function durableSourceNamespace(): string
    {
        return $this->sourceNamespace ?? $this->source->value;
    }

    public function withEntryRt(Rt $entryRt): self
    {
        return new self(
            source: $this->source,
            externalEventId: $this->externalEventId,
            senderPhone: $this->senderPhone,
            message: $this->message,
            receivedAt: $this->receivedAt,
            entryRt: $entryRt,
            incidentRt: $this->incidentRt,
            sourceNamespace: $this->sourceNamespace,
            handoffToken: $this->handoffToken,
            claimedEntryRtCode: $this->claimedEntryRtCode,
            claimedEntryRwCode: $this->claimedEntryRwCode,
        );
    }
}
