<?php

namespace App\Enums;

enum HumanOversightRequirement: string
{
    case NONE = 'none';
    case VERIFICATION = 'verification';
    case APPROVAL = 'approval';
    case OPERATOR_REQUIRED = 'operator_required';
}
