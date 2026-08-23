<?php

namespace App\Enums;

enum ReportCategory: string
{
    case ROAD_DAMAGE = 'ROAD_DAMAGE';
    case STREET_LIGHT = 'STREET_LIGHT';
    case GARBAGE = 'GARBAGE';
    case DRAINAGE = 'DRAINAGE';
    case FALLEN_TREE = 'FALLEN_TREE';
    case FLOOD = 'FLOOD';
    case ELECTRICITY = 'ELECTRICITY';
    case PUBLIC_FACILITY = 'PUBLIC_FACILITY';
    case OTHER = 'OTHER';

    public function label(): string
    {
        return match ($this) {
            self::ROAD_DAMAGE => 'Jalan rusak',
            self::STREET_LIGHT => 'Lampu jalan',
            self::GARBAGE => 'Sampah',
            self::DRAINAGE => 'Drainase',
            self::FALLEN_TREE => 'Pohon tumbang',
            self::FLOOD => 'Banjir',
            self::ELECTRICITY => 'Kelistrikan',
            self::PUBLIC_FACILITY => 'Fasilitas umum',
            self::OTHER => 'Lainnya',
        };
    }
}
