<?php

namespace App\Enums;

enum RequesterIdentityType: string
{
    case UNKNOWN = 'unknown';
    case KNOWN_CITIZEN = 'known_citizen';
    case STAFF = 'staff';
}
