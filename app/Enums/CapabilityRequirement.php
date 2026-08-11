<?php

namespace App\Enums;

/**
 * Requirement strength for a capability input such as identity or territory.
 */
enum CapabilityRequirement: string
{
    case REQUIRED = 'required';
    case OPTIONAL = 'optional';
    case NOT_APPLICABLE = 'not_applicable';
}
