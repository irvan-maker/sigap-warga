<?php

namespace App\Enums;

/**
 * Safe durable reasons for understood requests that did not execute here.
 */
enum InboundProcessingReason: string
{
    case IDENTITY_REQUIRED = 'identity_required';
    case TERRITORY_REQUIRED = 'territory_required';
    case IDENTITY_AND_TERRITORY_REQUIRED = 'identity_and_territory_required';
    case AUTHORIZATION_REQUIRED = 'authorization_required';
    case ROUTING_NOT_READY = 'routing_not_ready';
    case INVALID_INTENT_OR_ROUTING = 'invalid_intent_or_routing';
    case PENDING_SERVICE_ACTION = 'pending_service_action';
}
