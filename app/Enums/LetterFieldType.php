<?php

namespace App\Enums;

enum LetterFieldType: string
{
    case TEXT = 'text';
    case TEXTAREA = 'textarea';
    case DATE = 'date';
    case NUMBER = 'number';
    case SELECT = 'select';
    case BOOLEAN = 'boolean';

    public function label(): string
    {
        return match ($this) {
            self::TEXT => 'Teks singkat',
            self::TEXTAREA => 'Teks panjang',
            self::DATE => 'Tanggal',
            self::NUMBER => 'Angka',
            self::SELECT => 'Pilihan',
            self::BOOLEAN => 'Ya/Tidak',
        };
    }
}
