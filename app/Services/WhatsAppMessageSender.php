<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

final class WhatsAppMessageSender
{
    public function sendText(string $recipient, string $message): bool
    {
        try {
            $this->sendTextOrFail($recipient, $message);

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    public function sendTextOrFail(string $recipient, string $message): void
    {
        if (config('services.whatsapp.outbound_enabled') !== true) {
            throw new RuntimeException('WhatsApp outbound messaging is disabled.');
        }

        $token = config('services.whatsapp.access_token');
        $phoneNumberId = config('services.whatsapp.phone_number_id');
        $version = config('services.whatsapp.graph_version');

        if (! is_string($token) || $token === ''
            || ! is_string($phoneNumberId) || $phoneNumberId === ''
            || ! is_string($version) || preg_match('/\Av\d+\.\d+\z/', $version) !== 1) {
            throw new RuntimeException('WhatsApp outbound configuration is incomplete.');
        }

        $response = Http::withToken($token)
            ->acceptJson()
            ->timeout(10)
            ->retry(2, 250, throw: false)
            ->post("https://graph.facebook.com/{$version}/{$phoneNumberId}/messages", [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $recipient,
                'type' => 'text',
                'text' => [
                    'preview_url' => false,
                    'body' => $message,
                ],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException("WhatsApp API returned HTTP {$response->status()}.");
        }
    }

    /**
     * @param list<string> $bodyParameters
     */
    public function sendTemplate(
        string $recipient,
        string $templateName,
        string $languageCode,
        array $bodyParameters = [],
    ): bool {
        try {
            $this->sendTemplateOrFail($recipient, $templateName, $languageCode, $bodyParameters);

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @param list<string> $bodyParameters
     */
    public function sendTemplateOrFail(
        string $recipient,
        string $templateName,
        string $languageCode,
        array $bodyParameters = [],
    ): void {
        if (config('services.whatsapp.outbound_enabled') !== true) {
            throw new RuntimeException('WhatsApp outbound messaging is disabled.');
        }

        $token = config('services.whatsapp.access_token');
        $phoneNumberId = config('services.whatsapp.phone_number_id');
        $version = config('services.whatsapp.graph_version');

        if (! is_string($token) || $token === ''
            || ! is_string($phoneNumberId) || $phoneNumberId === ''
            || ! is_string($version) || preg_match('/\Av\d+\.\d+\z/', $version) !== 1) {
            throw new RuntimeException('WhatsApp outbound configuration is incomplete.');
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $recipient,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => ['code' => $languageCode],
                'components' => [[
                    'type' => 'body',
                    'parameters' => array_map(
                        static fn (string $value): array => ['type' => 'text', 'text' => $value],
                        $bodyParameters,
                    ),
                ]],
            ],
        ];

        $response = Http::withToken($token)->acceptJson()->timeout(10)->retry(2, 250, throw: false)
            ->post("https://graph.facebook.com/{$version}/{$phoneNumberId}/messages", $payload);

        if (! $response->successful()) {
            throw new RuntimeException("WhatsApp API returned HTTP {$response->status()}.");
        }
    }
}
