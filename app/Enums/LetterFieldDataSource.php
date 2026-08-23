<?php

namespace App\Enums;

enum LetterFieldDataSource: string
{
    case CITIZEN = 'CITIZEN';
    case FAMILY_CARD = 'FAMILY_CARD';

    public function label(): string
    {
        return match ($this) {
            self::CITIZEN => 'Master warga',
            self::FAMILY_CARD => 'Master kartu keluarga',
        };
    }
}
