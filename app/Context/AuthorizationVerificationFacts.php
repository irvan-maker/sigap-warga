<?php

namespace App\Context;

use App\Enums\VerificationState;

/**
 * Explicit facts supplied by a trusted caller boundary; nothing is inferred.
 */
final readonly class AuthorizationVerificationFacts
{
    public function __construct(
        public ?VerificationState $requester = null,
        public ?VerificationState $subject = null,
        public ?VerificationState $relationship = null,
        public ?VerificationState $staffScope = null,
    ) {}
}
