<?php

namespace App\Services;

use App\Context\TrustedInboundEvent;
use App\Context\TrustedInboundProcessingResult;
use App\Enums\CitizenIntent;
use App\Enums\ServiceEligibilityReason;
use App\Enums\TrustedInboundProcessingOutcome;
use App\Models\Rt;
use App\Models\WhatsAppConversation;
use App\Support\PhoneNumberNormalizer;
use DomainException;
use Illuminate\Support\Facades\Cache;

final class WhatsAppConversationService
{
    private const TTL_HOURS = 24;

    public function __construct(
        private readonly PhoneNumberNormalizer $phoneNumberNormalizer,
    ) {}


    public function hasInvalidPendingLocation(TrustedInboundEvent $event): bool     {         $pending = Cache::get($this->pendingKey($event));          if (! is_string($pending) || trim($pending) === "") {             return false;         }          if (preg_match("/^\\s*RT.*RW.*$/iu", trim($event->message)) !== 1) {             return false;         }          return $this->resolveManualLocation($event->message) === null;     }      public function resumePendingLocation(TrustedInboundEvent $event): TrustedInboundEvent
    {
        if ($event->incidentRt !== null) {
            return $event;
        }

        $pending = Cache::get($this->pendingKey($event));

        if (! is_string($pending) || trim($pending) === '') {
            return $event;
        }

        $incidentRt = $this->resolveManualLocation($event->message);

        if ($incidentRt === null) {
            return $event;
        }

        return new TrustedInboundEvent(
            source: $event->source,
            externalEventId: $event->externalEventId,
            senderPhone: $event->senderPhone,
            message: trim($pending),
            receivedAt: $event->receivedAt,
            entryRt: $event->entryRt,
            incidentRt: $incidentRt,
            sourceNamespace: $event->sourceNamespace,
            handoffToken: $event->handoffToken,
            claimedEntryRtCode: $event->claimedEntryRtCode,
            claimedEntryRwCode: $event->claimedEntryRwCode,
        );
    }

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

        if ($result->outcome === TrustedInboundProcessingOutcome::BLOCKED
            && $result->routingDecision?->intent === CitizenIntent::REPORT
            && in_array($result->eligibilityDecision?->reason, [
                ServiceEligibilityReason::TERRITORY_REQUIRED,
                ServiceEligibilityReason::IDENTITY_AND_TERRITORY_REQUIRED,
            ], true)) {
            Cache::put(
                $this->pendingKey($event),
                $event->message,
                now()->addHours(self::TTL_HOURS),
            );

            return;
        }

        if ($result->outcome === TrustedInboundProcessingOutcome::REPORT_CREATED) {
            Cache::forget($this->pendingKey($event));
        }

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


    private function pendingKey(TrustedInboundEvent $event): string
    {
        return 'whatsapp:pending-report:'
            .$event->durableSourceNamespace().':'
            .$this->participantHash($event->senderPhone);
    }

    private function resolveManualLocation(string $message): ?Rt
    {
        if (preg_match(
            '/^\s*RT\s*[-.:]?\s*([\p{L}\p{N}._-]+)\s*(?:\/|,|;|\s)+\s*RW\s*[-.:]?\s*([\p{L}\p{N}._-]+)\s*$/iu',
            trim($message),
            $matches,
        ) !== 1) {
            return null;
        }

        $rtToken = $this->normalizeTerritoryCode($matches[1], 'RT');
        $rwToken = $this->normalizeTerritoryCode($matches[2], 'RW');

        return Rt::query()
            ->with('rw')
            ->where('is_active', true)
            ->whereHas('rw', fn ($query) => $query->where('is_active', true))
            ->get()
            ->first(fn (Rt $rt): bool => $rt->rw !== null
                && $this->normalizeTerritoryCode($rt->code, 'RT') === $rtToken
                && $this->normalizeTerritoryCode($rt->rw->code, 'RW') === $rwToken);
    }

    private function normalizeTerritoryCode(string $value, string $prefix): string
    {
        $normalized = mb_strtoupper(trim($value));
        $normalized = preg_replace(
            '/^'.preg_quote($prefix, '/').'[\s._-]*/u',
            '',
            $normalized,
        ) ?? $normalized;
        $normalized = preg_replace('/[^\p{L}\p{N}]+/u', '', $normalized) ?? $normalized;

        if ($normalized !== '' && ctype_digit($normalized)) {
            return (string) ((int) $normalized);
        }

        return $normalized;
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
