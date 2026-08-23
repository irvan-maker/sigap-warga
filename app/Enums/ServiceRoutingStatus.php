<?php

namespace App\Enums;

enum ServiceRoutingStatus: string
{
    case ROUTABLE = 'routable';
    case BLOCKED = 'blocked';
}
