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
}
