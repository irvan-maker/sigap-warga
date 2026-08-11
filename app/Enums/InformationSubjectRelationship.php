<?php

namespace App\Enums;

/**
 * A caller-supplied relationship fact, never an authorization result.
 */
enum InformationSubjectRelationship: string
{
    case SELF = 'self';
    case OTHER_CITIZEN = 'other_citizen';
    case HOUSEHOLD_MEMBER = 'household_member';
    case SAME_TERRITORY = 'same_territory';
    case GOVERNMENT_SCOPE = 'government_scope';
    case UNKNOWN = 'unknown';
}
