<?php

namespace App\Services;

use App\Context\WhatsAppHandoffMarker;

final class WhatsAppHandoffMarkerParser
{
    public function __construct(private readonly OpaqueToken $tokens) {}

    public function extract(string $message): WhatsAppHandoffMarker
    {
        preg_match_all('/\[SW:([^\]\s]+)\]/i', $message, $matches);
        $candidates = $matches[1] ?? [];
        $valid = array_values(array_filter(
            $candidates,
            fn (string $candidate): bool => $this->tokens->isHandoffToken($candidate),
        ));
        $markerLikeCount = preg_match_all('/\[SW:[^\]]*\]/i', $message);
        $ambiguous = $markerLikeCount !== 1 || count($valid) !== 1;
        $clean = preg_replace('/\[SW:[^\]]*\]/i', ' ', $message) ?? $message;
        $clean = trim(preg_replace('/\s+/u', ' ', $clean) ?? '');

        return new WhatsAppHandoffMarker(
            message: $clean,
            token: $ambiguous ? null : $valid[0],
            ambiguous: $markerLikeCount > 0 && $ambiguous,
        );
    }
}
