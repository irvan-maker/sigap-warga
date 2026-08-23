<?php

namespace App\Enums;

enum LetterRequirementSubmissionStatus: string
{
    case PROVIDED = 'PROVIDED';
    case PENDING_VERIFICATION = 'PENDING_VERIFICATION';
    case NOT_PROVIDED = 'NOT_PROVIDED';

    public function label(): string
    {
        return match ($this) {
            self::PROVIDED => 'Dokumen diterima',
            self::PENDING_VERIFICATION => 'Perlu verifikasi petugas',
            self::NOT_PROVIDED => 'Tidak dilampirkan',
        };
    }

    public function bootstrapColor(): string
    {
        return match ($this) {
            self::PROVIDED => 'success',
            self::PENDING_VERIFICATION => 'warning',
            self::NOT_PROVIDED => 'secondary',
        };
    }
}
