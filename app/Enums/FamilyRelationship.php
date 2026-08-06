<?php

namespace App\Enums;

enum FamilyRelationship: string
{
    case HEAD = 'HEAD';
    case SPOUSE = 'SPOUSE';
    case CHILD = 'CHILD';
    case PARENT = 'PARENT';
    case IN_LAW = 'IN_LAW';
    case SIBLING = 'SIBLING';
    case OTHER = 'OTHER';

    public function label(): string
    {
        return match ($this) {
            self::HEAD => 'Kepala Keluarga',
            self::SPOUSE => 'Suami/Istri',
            self::CHILD => 'Anak',
            self::PARENT => 'Orang Tua',
            self::IN_LAW => 'Mertua',
            self::SIBLING => 'Saudara',
            self::OTHER => 'Lainnya',
        };
    }
}
