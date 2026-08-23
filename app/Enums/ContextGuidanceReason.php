<?php

namespace App\Enums;

enum ContextGuidanceReason: string
{
    case CONTEXT_READY = 'context_ready';
    case IDENTITY_REQUIRED = 'identity_required';
    case TERRITORY_REQUIRED = 'territory_required';
    case IDENTITY_AND_TERRITORY_REQUIRED = 'identity_and_territory_required';
    case TERRITORY_CONFIRMATION_REQUIRED = 'territory_confirmation_required';
    case IDENTITY_REACTIVATION_REQUIRED = 'identity_reactivation_required';
}
