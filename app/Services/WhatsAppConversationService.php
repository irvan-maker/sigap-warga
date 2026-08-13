<?php

namespace App\Services;

use App\Context\TrustedInboundEvent;
use App\Context\TrustedInboundProcessingResult;
use App\Models\WhatsAppConversation;
use App\Support\PhoneNumberNormalizer;
use DomainException;

final class WhatsAppConversationService
{
    private const TTL_HOURS = 24;

    public function __construct(
        private readonly PhoneNumberNormalizer $phoneNumberNormalizer,
    ) {}

    public function applyActiveContext(TrustedInboundEvent $event): TrustedInboundEvent
    {
        if ($event->entryRt !== null
            || $event->handoffToken !== null
            || $event->claimedEntryRtCode !== null
            || $event->claimedEntryRwCode !== null) {
            return $event;
        }

        $conversation = WhatsAppConversation::query()
            ->with('entryRt')
            ->where('source', $event->durableSourceNamespace())
            ->where('participant_hash', $this->participantHash($event->senderPhone))
            ->where('state', 'ACTIVE')
            ->where('expires_at', '>', now())
            ->first();

        if ($conversation?->entryRt?->isAvailableForService() !== true) {
            return $event;
        }

        $conversation->update([
            'last_interaction_at' => now(),
            'expires_at' => now()->addHours(self::TTL_HOURS),
        ]);

        return $event->withEntryRt($conversation->entryRt);
    }

    public function remember(
        TrustedInboundEvent $event,
        TrustedInboundProcessingResult $result,
    ): void {
        $context = $result->understanding?->serviceUnderstanding->contextResult->context;
        $entryRt = $context?->entryRt;

        if ($entryRt === null) {
            return;
        }

        WhatsAppConversation::query()->updateOrCreate(
            [
                'source' => $event->durableSourceNamespace(),
                'participant_hash' => $this->participantHash($event->senderPhone),
            ],
            [
                'entry_rt_id' => $entryRt->getKey(),
                'citizen_id' => $context->citizen?->getKey(),
                'service_hint' => 'report',
                'state' => 'ACTIVE',
                'last_interaction_at' => now(),
                'expires_at' => now()->addHours(self::TTL_HOURS),
            ],
        );
    }

    private function participantHash(string $phone): string
    {
        $key = config('app.key');

        if (! is_string($key) || $key === '') {
            throw new DomainException('Application key is required for conversation privacy.');
        }

        return hash_hmac(
            'sha256',
            $this->phoneNumberNormalizer->normalize($phone),
            $key,
        );
    }
}
