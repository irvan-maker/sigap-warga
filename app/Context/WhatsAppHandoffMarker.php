<?php

namespace App\Context;

final readonly class WhatsAppHandoffMarker
{
    public function __construct(
        public string $message,
        public ?string $token,
        public bool $ambiguous = false,
    ) {}
}
