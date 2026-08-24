<?php

namespace App\Enums;

enum LetterStatus: string
{
    case DRAFT = 'DRAFT';
    case SUBMITTED = 'SUBMITTED';
    case RW_REVIEWED = 'RW_REVIEWED';
    case APPROVED = 'APPROVED';
    case SIGNED = 'SIGNED';
    case REJECTED = 'REJECTED';
    case ISSUED = 'ISSUED';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::SUBMITTED => 'Diajukan',
            self::RW_REVIEWED => 'Diverifikasi RW',
            self::APPROVED => 'Disetujui',
            self::SIGNED => 'Ditandatangani',
            self::REJECTED => 'Ditolak',
            self::ISSUED => 'Diterbitkan',
        };
    }

    public function bootstrapColor(): string
    {
        return match ($this) {
            self::DRAFT => 'secondary',
            self::SUBMITTED => 'primary',
            self::RW_REVIEWED => 'info',
            self::APPROVED => 'warning',
            self::SIGNED => 'info',
            self::REJECTED => 'danger',
            self::ISSUED => 'success',
        };
    }
}