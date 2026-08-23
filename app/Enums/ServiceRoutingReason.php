<?php

namespace App\Enums;

/**
 * Successful routing reasons. Blocked decisions preserve the existing
 * RoutingReadinessReason instead of duplicating its vocabulary here.
 */
enum ServiceRoutingReason: string
{
    case ROUTED_TO_REPORT = 'routed_to_report';
    case ROUTED_TO_EMERGENCY = 'routed_to_emergency';
    case ROUTED_TO_LETTER = 'routed_to_letter';
    case ROUTED_TO_INFORMATION = 'routed_to_information';
    case ROUTED_TO_ASPIRATION = 'routed_to_aspiration';
}
