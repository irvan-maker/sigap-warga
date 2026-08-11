<?php

namespace App\Enums;

/**
 * A routing destination only; selecting one does not execute its service.
 */
enum ServiceRouteTarget: string
{
    case REPORT_SERVICE = 'report_service';
    case EMERGENCY_SERVICE = 'emergency_service';
    case LETTER_SERVICE = 'letter_service';
    case INFORMATION_SERVICE = 'information_service';
    case ASPIRATION_SERVICE = 'aspiration_service';
    case MANUAL_CLARIFICATION = 'manual_clarification';
}
