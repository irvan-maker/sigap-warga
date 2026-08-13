<?php

namespace App\Enums;

enum ReportHandlingLevel: string
{
    case RT = 'RT';
    case RW = 'RW';
    case KELURAHAN = 'KELURAHAN';

    public function label(): string
    {
        return match ($this) {
            self::RT => 'RT',
            self::RW => 'RW',
            self::KELURAHAN => 'Kelurahan',
        };
    }
}
