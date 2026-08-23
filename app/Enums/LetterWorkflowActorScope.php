<?php

namespace App\Enums;

enum LetterWorkflowActorScope: string
{
    case CITIZEN = 'CITIZEN';
    case RT = 'RT';
    case RW = 'RW';
    case KELURAHAN = 'KELURAHAN';

    public function label(): string
    {
        return match ($this) {
            self::CITIZEN => 'Warga',
            self::RT => 'RT',
            self::RW => 'RW',
            self::KELURAHAN => 'Desa',
        };
    }
}
