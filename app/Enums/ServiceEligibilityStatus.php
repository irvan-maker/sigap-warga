<?php

namespace App\Enums;

enum ServiceEligibilityStatus: string
{
    case ELIGIBLE = 'eligible';
    case BLOCKED = 'blocked';
}
