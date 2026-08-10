<?php

namespace App\Enums;

enum IntentUrgencyValidationReason: string
{
    case VALID_COMBINATION = 'valid_combination';
    case EMERGENCY_INTENT_REQUIRES_EMERGENCY_URGENCY = 'emergency_intent_requires_emergency_urgency';
    case EMERGENCY_URGENCY_REQUIRES_EMERGENCY_INTENT = 'emergency_urgency_requires_emergency_intent';
    case NORMAL_URGENCY_REQUIRED = 'normal_urgency_required';
}
