<?php

namespace App\Services;

final class WhatsAppWebhookSignatureVerifier
{
    public function verify(string $rawBody, ?string $signatureHeader): bool
    {
        $appSecret = config('services.whatsapp.app_secret');

        if (! is_string($appSecret) || $appSecret === '' || $signatureHeader === null) {
            return false;
        }

        if (preg_match('/\Asha256=([a-f0-9]{64})\z/', $signatureHeader, $matches) !== 1) {
            return false;
        }

        $expectedDigest = hash_hmac('sha256', $rawBody, $appSecret);

        return hash_equals($expectedDigest, $matches[1]);
    }
}
