<?php

namespace App\Enums;

/**
 * The citizen's service need, independent of how it was identified.
 *
 * REPORT covers tracked, non-emergency reports. EMERGENCY is intentionally a
 * separate intent because it must not be forced into the normal report flow.
 */
enum CitizenIntent: string
{
    case REPORT = 'report';
    case EMERGENCY = 'emergency';
    case LETTER = 'letter';
    case INFORMATION = 'information';
    case ASPIRATION = 'aspiration';
    case UNKNOWN = 'unknown';
}
