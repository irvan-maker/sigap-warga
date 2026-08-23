<?php

namespace App\Enums;

enum LetterWorkflowAction: string
{
    case SUBMIT = 'SUBMIT';
    case VERIFY = 'VERIFY';
    case APPROVE = 'APPROVE';
    case SIGN = 'SIGN';
    case ISSUE = 'ISSUE';

    public function label(): string
    {
        return match ($this) {
            self::SUBMIT => 'Ajukan',
            self::VERIFY => 'Verifikasi',
            self::APPROVE => 'Setujui',
            self::SIGN => 'Tandatangani',
            self::ISSUE => 'Terbitkan',
        };
    }
}
