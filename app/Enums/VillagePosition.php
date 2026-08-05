<?php

namespace App\Enums;

enum VillagePosition: string
{
    case SYSTEM_ADMIN = 'SYSTEM_ADMIN';
    case VILLAGE_HEAD = 'VILLAGE_HEAD';
    case VILLAGE_SECRETARY = 'VILLAGE_SECRETARY';

    public function label(): string
    {
        return match ($this) {
            self::SYSTEM_ADMIN => 'Administrator Sistem',
            self::VILLAGE_HEAD => 'Kepala Desa',
            self::VILLAGE_SECRETARY => 'Sekretaris Desa',
        };
    }
}
