<?php

namespace App\Enums;

enum ReportStatus: string
{
    case NEW = 'NEW';
    case PROCESSING = 'PROCESSING';
    case COMPLETED = 'COMPLETED';
    case REJECTED = 'REJECTED';
}
