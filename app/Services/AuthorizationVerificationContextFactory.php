<?php

namespace App\Services;

use App\Context\AuthorizationVerificationContext;
use App\Context\AuthorizationVerificationFacts;
use App\Context\ProtectedInformationAuthorizationContext;
use App\Enums\InformationAccessLevel;
use App\Enums\RequesterIdentityType;
use App\Enums\VerificationState;

/**
 * Maps caller-supplied verification facts without proving or authorizing them.
 */
final class AuthorizationVerificationContextFactory
{
    public function create(
        ProtectedInformationAuthorizationContext $authorizationContext,
        ?AuthorizationVerificationFacts $facts = null,
    ): AuthorizationVerificationContext {
        if ($authorizationContext->classification->accessLevel === InformationAccessLevel::PUBLIC) {
            return new AuthorizationVerificationContext(
                requester: VerificationState::NOT_APPLICABLE,
                subject: VerificationState::NOT_APPLICABLE,
                relationship: VerificationState::NOT_APPLICABLE,
                staffScope: VerificationState::NOT_APPLICABLE,
            );
        }

        return new AuthorizationVerificationContext(
            requester: $this->required($facts?->requester),
            subject: $this->required($facts?->subject),
            relationship: $this->required($facts?->relationship),
            staffScope: $authorizationContext->requester->type === RequesterIdentityType::STAFF
                ? $this->required($facts?->staffScope)
                : VerificationState::NOT_APPLICABLE,
        );
    }

    private function required(?VerificationState $supplied): VerificationState
    {
        return match ($supplied) {
            VerificationState::VERIFIED => VerificationState::VERIFIED,
            VerificationState::CONFLICTING => VerificationState::CONFLICTING,
            default => VerificationState::UNVERIFIED,
        };
    }
}
