<?php

namespace App\Enums;

enum LetterApprovalLevel: string
{
    case RT = 'RT';
    case RW = 'RW';
    case KELURAHAN = 'KELURAHAN';

    public function label(): string
    {
        return match ($this) {
            self::RT => 'Cukup RT',
            self::RW => 'Sampai RW',
            self::KELURAHAN => 'Sampai Kelurahan',
        };
    }
}
