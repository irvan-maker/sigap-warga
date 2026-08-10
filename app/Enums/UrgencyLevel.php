<?php

namespace App\Enums;

/**
 * Priority semantics only; this enum does not define SLA or workflow.
 *
 * A quick report remains REPORT with NORMAL urgency, a high-priority report
 * remains REPORT with HIGH urgency, and an emergency uses EMERGENCY intent and
 * EMERGENCY urgency with a future dedicated workflow.
 */
enum UrgencyLevel: string
{
    case NORMAL = 'normal';
    case HIGH = 'high';
    case EMERGENCY = 'emergency';
}
