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

    public static function forIntent(CitizenIntent $intent): ?self
    {
        return match ($intent) {
            CitizenIntent::REPORT => self::REPORT_SERVICE,
            CitizenIntent::EMERGENCY => self::EMERGENCY_SERVICE,
            CitizenIntent::LETTER => self::LETTER_SERVICE,
            CitizenIntent::INFORMATION => self::INFORMATION_SERVICE,
            CitizenIntent::ASPIRATION => self::ASPIRATION_SERVICE,
            CitizenIntent::UNKNOWN => null,
        };
    }
}
