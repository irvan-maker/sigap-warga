<?php

namespace App\Enums;

enum IntentUrgencyValidationStatus: string
{
    case VALID = 'valid';
    case INVALID = 'invalid';
}
