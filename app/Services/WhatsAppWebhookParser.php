<?php

namespace App\Services;

use App\Context\TrustedInboundEvent;
use App\Context\WhatsAppInboundAdapterResult;
use App\Enums\InboundSource;
use DateTimeImmutable;
use DomainException;

/**
 * Extracts only supported inbound text messages from a verified Meta payload.
 */
final class WhatsAppWebhookParser
{
    public function __construct(
        private readonly WhatsAppHandoffMarkerParser $handoffMarkerParser,
        private readonly WhatsAppEntryReferenceParser $entryReferenceParser,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function parse(array $payload): WhatsAppInboundAdapterResult
    {
        if (($payload['object'] ?? null) !== 'whatsapp_business_account') {
            return new WhatsAppInboundAdapterResult([], 1);
        }

        $events = [];
        $ignoredCount = 0;

        foreach ($this->arrays($payload['entry'] ?? null) as $entry) {
            if (! $this->matchesConfiguredAccount($entry['id'] ?? null, 'waba_id')) {
                $ignoredCount++;

                continue;
            }

            foreach ($this->arrays($entry['changes'] ?? null) as $change) {
                if (($change['field'] ?? null) !== 'messages' || ! is_array($change['value'] ?? null)) {
                    $ignoredCount++;

                    continue;
                }

                $value = $change['value'];
                $phoneNumberId = $value['metadata']['phone_number_id'] ?? null;
                $messages = $this->arrays($value['messages'] ?? null);

                if (! $this->matchesConfiguredAccount($phoneNumberId, 'phone_number_id')) {
                    $ignoredCount += max(1, count($messages));

                    continue;
                }

                foreach ($messages as $message) {
                    $event = $this->textEvent($message, $phoneNumberId);

                    if ($event === null) {
                        $ignoredCount++;
                    } else {
                        $events[] = $event;
                    }
                }

                if ($messages === []) {
                    // Delivery/status changes intentionally do not create receipts.
                    $ignoredCount++;
                }
            }
        }

        return new WhatsAppInboundAdapterResult($events, $ignoredCount);
    }

    private function matchesConfiguredAccount(mixed $payloadId, string $configKey): bool
    {
        $configuredId = config("services.whatsapp.{$configKey}");

        if (! is_string($configuredId) || trim($configuredId) === '') {
            throw new DomainException("WhatsApp {$configKey} is not configured.");
        }

        return is_string($payloadId) && hash_equals(trim($configuredId), trim($payloadId));
    }

    /**
     * @param  array<string, mixed>  $message
     */
    private function textEvent(array $message, string $phoneNumberId): ?TrustedInboundEvent
    {
        $messageId = $message['id'] ?? null;
        $sender = $message['from'] ?? null;
        $body = $message['text']['body'] ?? null;

        if (($message['type'] ?? null) !== 'text'
            || ! is_string($messageId)
            || trim($messageId) === ''
            || ! is_string($sender)
            || trim($sender) === ''
            || ! is_string($body)) {
            return null;
        }

        $entryReference = $this->entryReferenceParser->extract($body);
        $marker = $this->handoffMarkerParser->extract($entryReference->message);

        return new TrustedInboundEvent(
            source: InboundSource::META_WHATSAPP,
            externalEventId: $messageId,
            senderPhone: $sender,
            message: $marker->message,
            receivedAt: $this->receivedAt($message['timestamp'] ?? null),
            entryRt: null,
            incidentRt: null,
            sourceNamespace: $this->sourceNamespace($phoneNumberId),
            handoffToken: $marker->token,
            claimedEntryRtCode: $entryReference->rtCode,
            claimedEntryRwCode: $entryReference->rwCode,
        );
    }

    private function sourceNamespace(string $phoneNumberId): string
    {
        $configuredNamespace = config('services.whatsapp.source_namespace');

        if (! is_string($configuredNamespace) || trim($configuredNamespace) === '') {
            throw new DomainException('WhatsApp source namespace is not configured.');
        }

        $namespace = trim($configuredNamespace).':'.trim($phoneNumberId);

        if (mb_strlen($namespace) > 64) {
            throw new DomainException('WhatsApp source namespace exceeds the inbound limit.');
        }

        return $namespace;
    }

    private function receivedAt(mixed $timestamp): DateTimeImmutable
    {
        if (is_string($timestamp) && ctype_digit($timestamp)) {
            return new DateTimeImmutable('@'.$timestamp);
        }

        return new DateTimeImmutable;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function arrays(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter($value, is_array(...)));
    }
}
