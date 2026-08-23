<?php

namespace App\Enums;

enum AuthorizationContextStatus: string
{
    case COMPLETE = 'complete';
    case INCOMPLETE = 'incomplete';
    case NOT_APPLICABLE = 'not_applicable';
}
