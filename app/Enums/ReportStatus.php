<?php

namespace App\Enums;

enum ReportStatus: string
{
    case NEW = 'NEW';
    case PROCESSING = 'PROCESSING';
    case FORWARDED = 'FORWARDED';
    case COMPLETED = 'COMPLETED';
    case REJECTED = 'REJECTED';

    public function label(): string
    {
        return match ($this) {
            self::NEW => 'Baru',
            self::PROCESSING => 'Diproses',
            self::FORWARDED => 'Diteruskan',
            self::COMPLETED => 'Selesai',
            self::REJECTED => 'Ditolak',
        };
    }

    public function bootstrapColor(): string
    {
        return match ($this) {
            self::NEW => 'primary',
            self::PROCESSING => 'warning',
            self::FORWARDED => 'info',
            self::COMPLETED => 'success',
            self::REJECTED => 'danger',
        };
    }

    public function initial(): string
    {
        return match ($this) {
            self::NEW => 'B',
            self::PROCESSING => 'P',
            self::FORWARDED => 'T',
            self::COMPLETED => 'S',
            self::REJECTED => 'D',
        };
    }
}
