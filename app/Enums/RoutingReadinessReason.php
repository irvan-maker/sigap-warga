<?php

namespace App\Enums;

enum RoutingReadinessReason: string
{
    case READY = 'ready';
    case CONTEXT_INCOMPLETE = 'context_incomplete';
    case IDENTITY_REQUIRED = 'identity_required';
    case TERRITORY_REQUIRED = 'territory_required';
    case IDENTITY_AND_TERRITORY_REQUIRED = 'identity_and_territory_required';
    case TERRITORY_CONFIRMATION_REQUIRED = 'territory_confirmation_required';
    case IDENTITY_INACTIVE = 'identity_inactive';
    case INTENT_UNKNOWN = 'intent_unknown';
    case INTENT_URGENCY_INVALID = 'intent_urgency_invalid';
    case SERVICE_TERRITORY_UNRESOLVED = 'service_territory_unresolved';
}
