<?php

namespace App\Context;

final readonly class IssuedOpaqueToken
{
    public function __construct(
        public string $token,
        public object $record,
    ) {}
}
