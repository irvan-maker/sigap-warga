<?php

namespace App\Context;

use App\Enums\CitizenIntent;
use App\Enums\IntentRule;

final readonly class RuleBasedIntentResolution
{
    public function __construct(
        public IntentResolution $resolution,
        public IntentRule $matchedRule,
        public CitizenIntent $matchedCategory,
        public ?string $matchedPhrase = null,
    ) {}
}
