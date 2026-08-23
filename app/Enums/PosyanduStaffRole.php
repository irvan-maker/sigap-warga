<?php

namespace App\Enums;

enum PosyanduStaffRole: string
{
    case CADRE = 'CADRE';
    case HEALTH_OFFICER = 'HEALTH_OFFICER';
    case COORDINATOR = 'COORDINATOR';

    public function label(): string
    {
        return match ($this) {
            self::CADRE => 'Kader',
            self::HEALTH_OFFICER => 'Petugas kesehatan',
            self::COORDINATOR => 'Koordinator',
        };
    }
}
