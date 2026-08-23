<?php

namespace App\Enums;

enum ReportPriority: string
{
    case NORMAL = 'NORMAL';
    case HIGH = 'HIGH';
    case EMERGENCY = 'EMERGENCY';

    public function label(): string
    {
        return match ($this) {
            self::NORMAL => 'Normal',
            self::HIGH => 'Tinggi',
            self::EMERGENCY => 'Darurat',
        };
    }

    public function bootstrapColor(): string
    {
        return match ($this) {
            self::NORMAL => 'secondary',
            self::HIGH => 'warning',
            self::EMERGENCY => 'danger',
        };
    }
}
