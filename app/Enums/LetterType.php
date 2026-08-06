<?php

namespace App\Enums;

enum LetterType: string
{
    case GENERAL_INTRODUCTION = 'GENERAL_INTRODUCTION';
    case DOMICILE_CERTIFICATE = 'DOMICILE_CERTIFICATE';
    case LOW_INCOME_CERTIFICATE = 'LOW_INCOME_CERTIFICATE';

    public function label(): string
    {
        return match ($this) {
            self::GENERAL_INTRODUCTION => 'Surat Pengantar Umum', self::DOMICILE_CERTIFICATE => 'Surat Keterangan Domisili', self::LOW_INCOME_CERTIFICATE => 'Surat Keterangan Tidak Mampu'
        };
    }

    public function code(): string
    {
        return match ($this) {
            self::GENERAL_INTRODUCTION => 'SP-UM', self::DOMICILE_CERTIFICATE => 'SK-DOM', self::LOW_INCOME_CERTIFICATE => 'SK-TM'
        };
    }
}
