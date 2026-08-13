<?php

namespace App\Services;

use App\Enums\IntentRule;
use App\Enums\ReportCategory;
use App\Enums\ReportPriority;
use App\Enums\UrgencyLevel;

final class ReportClassifier
{
    public function category(IntentRule $rule): ReportCategory
    {
        return match ($rule) {
            IntentRule::REPORT_ROAD_DAMAGE,
            IntentRule::REPORT_HIGH_LANDSLIDE => ReportCategory::ROAD_DAMAGE,
            IntentRule::REPORT_STREET_LIGHT => ReportCategory::STREET_LIGHT,
            IntentRule::REPORT_GARBAGE => ReportCategory::GARBAGE,
            IntentRule::REPORT_DRAINAGE => ReportCategory::DRAINAGE,
            IntentRule::REPORT_HIGH_FALLEN_TREE => ReportCategory::FALLEN_TREE,
            IntentRule::REPORT_HIGH_FLOOD => ReportCategory::FLOOD,
            IntentRule::REPORT_HIGH_UTILITY_POLE => ReportCategory::ELECTRICITY,
            IntentRule::REPORT_PUBLIC_FACILITY => ReportCategory::PUBLIC_FACILITY,
            default => ReportCategory::OTHER,
        };
    }

    public function priority(UrgencyLevel $urgency): ReportPriority
    {
        return match ($urgency) {
            UrgencyLevel::NORMAL => ReportPriority::NORMAL,
            UrgencyLevel::HIGH => ReportPriority::HIGH,
            UrgencyLevel::EMERGENCY => ReportPriority::EMERGENCY,
        };
    }
}
