<?php

namespace App\Enums;

enum ReportDispositionStatus: string
{
    case PENDING = 'PENDING';
    case ACKNOWLEDGED = 'ACKNOWLEDGED';
    case RETURNED = 'RETURNED';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Menunggu penerimaan',
            self::ACKNOWLEDGED => 'Sudah diterima',
            self::RETURNED => 'Dikembalikan',
        };
    }
}
