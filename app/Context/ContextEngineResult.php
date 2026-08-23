<?php

namespace App\Context;

final readonly class ContextEngineResult
{
    public function __construct(
        public ServiceContext $context,
        public ContextGuidance $guidance,
    ) {}
}
