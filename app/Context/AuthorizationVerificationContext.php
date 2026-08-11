<?php

namespace App\Context;

use App\Enums\VerificationState;

/**
 * Per-dimension verification state. This is not an authorization decision.
 *
 * This value object contains no requester model or protected subject data and
 * is not a response payload.
 */
final readonly class AuthorizationVerificationContext
{
    public function __construct(
        public VerificationState $requester,
        public VerificationState $subject,
        public VerificationState $relationship,
        public VerificationState $staffScope,
    ) {}

    public function hasConflict(): bool
    {
        return in_array(VerificationState::CONFLICTING, $this->states(), true);
    }

    /**
     * Whether all applicable facts are verified for future policy evaluation.
     * This does not mean access is allowed.
     */
    public function isFullyVerifiedForAuthorization(): bool
    {
        foreach ($this->states() as $state) {
            if (! in_array($state, [VerificationState::VERIFIED, VerificationState::NOT_APPLICABLE], true)) {
                return false;
            }
        }

        return true;
    }

    /** @return list<VerificationState> */
    private function states(): array
    {
        return [
            $this->requester,
            $this->subject,
            $this->relationship,
            $this->staffScope,
        ];
    }
}
