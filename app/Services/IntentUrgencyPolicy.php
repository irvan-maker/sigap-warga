<?php

namespace App\Services;

use App\Context\IntentResolution;
use App\Context\IntentUrgencyValidation;
use App\Enums\CitizenIntent;
use App\Enums\IntentUrgencyValidationReason;
use App\Enums\IntentUrgencyValidationStatus;
use App\Enums\UrgencyLevel;

class IntentUrgencyPolicy
{
    public function validate(IntentResolution $resolution): IntentUrgencyValidation
    {
        if ($resolution->intent === CitizenIntent::EMERGENCY) {
            return $resolution->urgency === UrgencyLevel::EMERGENCY
                ? $this->valid($resolution)
                : $this->invalid(
                    $resolution,
                    IntentUrgencyValidationReason::EMERGENCY_INTENT_REQUIRES_EMERGENCY_URGENCY,
                );
        }

        if ($resolution->urgency === UrgencyLevel::EMERGENCY) {
            return $this->invalid(
                $resolution,
                IntentUrgencyValidationReason::EMERGENCY_URGENCY_REQUIRES_EMERGENCY_INTENT,
            );
        }

        if ($resolution->intent === CitizenIntent::REPORT) {
            return $this->valid($resolution);
        }

        return $resolution->urgency === UrgencyLevel::NORMAL
            ? $this->valid($resolution)
            : $this->invalid(
                $resolution,
                IntentUrgencyValidationReason::NORMAL_URGENCY_REQUIRED,
            );
    }

    private function valid(IntentResolution $resolution): IntentUrgencyValidation
    {
        return new IntentUrgencyValidation(
            intent: $resolution->intent,
            urgency: $resolution->urgency,
            status: IntentUrgencyValidationStatus::VALID,
            reason: IntentUrgencyValidationReason::VALID_COMBINATION,
        );
    }

    private function invalid(
        IntentResolution $resolution,
        IntentUrgencyValidationReason $reason,
    ): IntentUrgencyValidation {
        return new IntentUrgencyValidation(
            intent: $resolution->intent,
            urgency: $resolution->urgency,
            status: IntentUrgencyValidationStatus::INVALID,
            reason: $reason,
        );
    }
}
