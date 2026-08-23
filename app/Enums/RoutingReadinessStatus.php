<?php

namespace App\Enums;

enum RoutingReadinessStatus: string
{
    case READY = 'ready';
    case BLOCKED = 'blocked';
}
