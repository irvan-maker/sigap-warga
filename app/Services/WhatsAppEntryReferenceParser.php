<?php

namespace App\Services;

use App\Context\WhatsAppEntryReference;

final class WhatsAppEntryReferenceParser
{
    private const REFERENCE_PATTERN = '/(?:^|\R)\s*Pintu layanan\s*:\s*\R\s*([\p{L}\p{N}._-]{1,100})\s*\/\s*([\p{L}\p{N}._-]{1,100})\s*$/iu';

    public function extract(string $message): WhatsAppEntryReference
    {
        preg_match_all(self::REFERENCE_PATTERN, $message, $matches, PREG_SET_ORDER);

        if (count($matches) !== 1) {
            return new WhatsAppEntryReference(
                message: trim($message),
                ambiguous: count($matches) > 1,
            );
        }

        $clean = preg_replace(self::REFERENCE_PATTERN, '', $message) ?? $message;

        return new WhatsAppEntryReference(
            message: trim($clean),
            rtCode: trim($matches[0][1]),
            rwCode: trim($matches[0][2]),
        );
    }
}
