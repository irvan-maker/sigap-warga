<?php

namespace App\Jobs;

use App\Context\TrustedInboundEvent;
use App\Enums\InboundSource;
use App\Enums\TrustedInboundProcessingOutcome;
use App\Models\Rt;
use App\Services\ProcessTrustedInboundEvent;
use App\Services\WhatsAppConversationService;
use App\Services\WhatsAppReplyFactory;
use DateTimeImmutable;
use DomainException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RuntimeException;

final class ProcessWhatsAppInboundEvent implements ShouldBeEncrypted, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    /** @var list<int> */
    public array $backoff = [5, 30, 120];

    public function __construct(
        public readonly string $source,
        public readonly string $externalEventId,
        public readonly string $senderPhone,
        public readonly string $message,
        public readonly string $receivedAt,
        public readonly ?int $entryRtId,
        public readonly ?int $incidentRtId,
        public readonly ?string $sourceNamespace,
        public readonly ?string $handoffToken,
        public readonly ?string $claimedEntryRtCode,
        public readonly ?string $claimedEntryRwCode,
    ) {
        $this->onQueue('whatsapp');
    }

    public static function fromEvent(TrustedInboundEvent $event): self
    {
        return new self(
            source: $event->source->value,
            externalEventId: $event->externalEventId,
            senderPhone: $event->senderPhone,
            message: $event->message,
            receivedAt: $event->receivedAt->format(DATE_ATOM),
            entryRtId: $event->entryRt?->getKey(),
            incidentRtId: $event->incidentRt?->getKey(),
            sourceNamespace: $event->sourceNamespace,
            handoffToken: $event->handoffToken,
            claimedEntryRtCode: $event->claimedEntryRtCode,
            claimedEntryRwCode: $event->claimedEntryRwCode,
        );
    }

    public function handle(
        ProcessTrustedInboundEvent $processor,
        WhatsAppConversationService $conversations,
        WhatsAppReplyFactory $replyFactory,
    ): void {
        $event = $this->event();

        try {
            $event = $conversations->applyActiveContext($event);
            $result = $processor->process($event);
            $conversations->remember($event, $result);
        } catch (DomainException) {
            // Invalid signed content is discarded without leaking trust details.
            return;
        }

        if ($result->outcome === TrustedInboundProcessingOutcome::FAILED) {
            throw new RuntimeException('Trusted WhatsApp inbound processing failed.');
        }

        $reply = $replyFactory->make($event, $result);

        if ($reply !== null && config('services.whatsapp.outbound_enabled') === true) {
            SendWhatsAppText::dispatch($event->senderPhone, $reply);
        }
    }

    private function event(): TrustedInboundEvent
    {
        return new TrustedInboundEvent(
            source: InboundSource::from($this->source),
            externalEventId: $this->externalEventId,
            senderPhone: $this->senderPhone,
            message: $this->message,
            receivedAt: new DateTimeImmutable($this->receivedAt),
            entryRt: $this->entryRtId === null ? null : Rt::query()->find($this->entryRtId),
            incidentRt: $this->incidentRtId === null ? null : Rt::query()->find($this->incidentRtId),
            sourceNamespace: $this->sourceNamespace,
            handoffToken: $this->handoffToken,
            claimedEntryRtCode: $this->claimedEntryRtCode,
            claimedEntryRwCode: $this->claimedEntryRwCode,
        );
    }
}
