<?php

namespace App\Enums;

/**
 * Conceptual eligibility only; ELIGIBLE does not execute or authorize work.
 */
enum ServiceExecutionEligibility: string
{
    case ELIGIBLE = 'eligible';
    case NOT_EXECUTABLE = 'not_executable';
}
