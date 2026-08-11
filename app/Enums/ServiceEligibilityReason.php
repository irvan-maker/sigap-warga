<?php

namespace App\Enums;

enum ServiceEligibilityReason: string
{
    case ELIGIBLE = 'eligible';
    case IDENTITY_REQUIRED = 'identity_required';
    case TERRITORY_REQUIRED = 'territory_required';
    case IDENTITY_AND_TERRITORY_REQUIRED = 'identity_and_territory_required';
    case ROUTING_NOT_READY = 'routing_not_ready';
    case INVALID_INTENT_OR_ROUTING = 'invalid_intent_or_routing';
}
