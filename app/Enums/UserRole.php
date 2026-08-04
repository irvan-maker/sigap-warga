<?php

namespace App\Enums;

enum UserRole: string
{
    case ADMIN = 'ADMIN';
    case RT = 'RT';
    case RW = 'RW';
    case KELURAHAN = 'KELURAHAN';
}
