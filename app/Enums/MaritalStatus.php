<?php

namespace App\Enums;

enum MaritalStatus: string
{
    case SINGLE = 'SINGLE';
    case MARRIED = 'MARRIED';
    case DIVORCED = 'DIVORCED';
    case WIDOWED = 'WIDOWED';

    public function label(): string
    {
        return match ($this) {
            self::SINGLE => 'Belum Kawin',
            self::MARRIED => 'Kawin',
            self::DIVORCED => 'Cerai Hidup',
            self::WIDOWED => 'Cerai Mati',
        };
    }
}
