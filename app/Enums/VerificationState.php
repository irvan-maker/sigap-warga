<?php

namespace App\Enums;

/**
 * Trust state only; VERIFIED does not mean access is authorized.
 */
enum VerificationState: string
{
    case UNVERIFIED = 'unverified';
    case VERIFIED = 'verified';
    case CONFLICTING = 'conflicting';
    case NOT_APPLICABLE = 'not_applicable';
}
