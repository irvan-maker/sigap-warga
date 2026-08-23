<?php

namespace App\Enums;

enum InformationClassificationReason: string
{
    case PUBLIC_RULE_MATCHED = 'public_rule_matched';
    case PROTECTED_RULE_MATCHED = 'protected_rule_matched';
    case DEFAULT_PROTECTED = 'default_protected';
}
