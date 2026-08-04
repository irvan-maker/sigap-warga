<?php

namespace App\Enums;

enum ReportStatus: string
{
    case NEW = 'NEW';
    case PROCESSING = 'PROCESSING';
    case COMPLETED = 'COMPLETED';
    case REJECTED = 'REJECTED';

    public function label(): string
    {
        return match ($this) {
            self::NEW => 'Baru',
            self::PROCESSING => 'Diproses',
            self::COMPLETED => 'Selesai',
            self::REJECTED => 'Ditolak',
        };
    }

    public function bootstrapColor(): string
    {
        return match ($this) {
            self::NEW => 'primary',
            self::PROCESSING => 'warning',
            self::COMPLETED => 'success',
            self::REJECTED => 'danger',
        };
    }

    public function initial(): string
    {
        return match ($this) {
            self::NEW => 'B',
            self::PROCESSING => 'P',
            self::COMPLETED => 'S',
            self::REJECTED => 'D',
        };
    }
}
