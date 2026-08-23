<?php

namespace App\Context;

use App\Enums\CitizenIntent;
use App\Enums\IntentUrgencyValidationReason;
use App\Enums\IntentUrgencyValidationStatus;
use App\Enums\UrgencyLevel;

final readonly class IntentUrgencyValidation
{
    public function __construct(
        public CitizenIntent $intent,
        public UrgencyLevel $urgency,
        public IntentUrgencyValidationStatus $status,
        public IntentUrgencyValidationReason $reason,
    ) {}

    public function isValid(): bool
    {
        return $this->status === IntentUrgencyValidationStatus::VALID;
    }
}
