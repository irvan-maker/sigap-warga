<?php

namespace App\Context;

use App\Models\Rt;

final readonly class EntryContext
{
    public function __construct(
        public ?string $channel = null,
        public ?string $message = null,
        public ?string $phone = null,
        public ?Rt $rt = null,
    ) {}
}
