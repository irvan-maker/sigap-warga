<?php

namespace App\Enums;

enum InboundRequestStatus: string
{
    case RECEIVED = 'RECEIVED';
    case PROCESSING = 'PROCESSING';
    case SUCCEEDED = 'SUCCEEDED';
    case BLOCKED = 'BLOCKED';
    case PENDING_ACTION = 'PENDING_ACTION';
    case FAILED = 'FAILED';
}
