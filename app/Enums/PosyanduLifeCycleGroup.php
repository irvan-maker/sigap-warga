<?php

namespace App\Enums;

enum PosyanduLifeCycleGroup: string
{
    case PREGNANT_POSTPARTUM = 'PREGNANT_POSTPARTUM';
    case INFANT_TODDLER = 'INFANT_TODDLER';
    case CHILD_ADOLESCENT = 'CHILD_ADOLESCENT';
    case PRODUCTIVE_AGE = 'PRODUCTIVE_AGE';
    case ELDERLY = 'ELDERLY';

    public function label(): string
    {
        return match ($this) {
            self::PREGNANT_POSTPARTUM => 'Ibu hamil/nifas',
            self::INFANT_TODDLER => 'Bayi/balita',
            self::CHILD_ADOLESCENT => 'Anak/remaja',
            self::PRODUCTIVE_AGE => 'Usia produktif',
            self::ELDERLY => 'Lansia',
        };
    }
}
