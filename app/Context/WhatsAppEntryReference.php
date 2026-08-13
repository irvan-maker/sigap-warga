<?php

namespace App\Context;

/**
 * A human-readable territory claim extracted from a WhatsApp message.
 *
 * The codes are not identity proof. They may only become an entry candidate
 * after the server verifies an active one-QR-per-RT service entry point.
 */
final readonly class WhatsAppEntryReference
{
    public function __construct(
        public string $message,
        public ?string $rtCode = null,
        public ?string $rwCode = null,
        public bool $ambiguous = false,
    ) {}
}
