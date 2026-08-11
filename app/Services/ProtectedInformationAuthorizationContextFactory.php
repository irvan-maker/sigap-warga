<?php

namespace App\Services;

use App\Context\InformationAccessClassification;
use App\Context\ProtectedInformationAuthorizationContext;
use App\Context\ProtectedInformationSubject;
use App\Context\RequesterIdentityContext;
use App\Enums\AuthorizationContextReason;
use App\Enums\AuthorizationContextStatus;
use App\Enums\InformationAccessLevel;
use App\Enums\InformationSubjectRelationship;
use App\Enums\RequesterIdentityType;
use App\Models\Rt;

/**
 * Assembles only caller-provided authorization facts without deciding access.
 */
final class ProtectedInformationAuthorizationContextFactory
{
    public function create(
        InformationAccessClassification $classification,
        RequesterIdentityContext $requester,
        ?ProtectedInformationSubject $subject = null,
        ?Rt $requesterTerritory = null,
    ): ProtectedInformationAuthorizationContext {
        if ($classification->accessLevel === InformationAccessLevel::PUBLIC) {
            return $this->context(
                classification: $classification,
                requester: $requester,
                subject: $subject,
                requesterTerritory: $requesterTerritory,
                status: AuthorizationContextStatus::NOT_APPLICABLE,
                reason: AuthorizationContextReason::PUBLIC_NOT_APPLICABLE,
            );
        }

        if ($requester->type === RequesterIdentityType::UNKNOWN) {
            return $this->context(
                classification: $classification,
                requester: $requester,
                subject: $subject,
                requesterTerritory: $requesterTerritory,
                status: AuthorizationContextStatus::INCOMPLETE,
                reason: AuthorizationContextReason::REQUESTER_REQUIRED,
            );
        }

        if ($subject === null) {
            return $this->context(
                classification: $classification,
                requester: $requester,
                subject: null,
                requesterTerritory: $requesterTerritory,
                status: AuthorizationContextStatus::INCOMPLETE,
                reason: AuthorizationContextReason::SUBJECT_REQUIRED,
            );
        }

        if ($subject->relationship === InformationSubjectRelationship::UNKNOWN) {
            return $this->context(
                classification: $classification,
                requester: $requester,
                subject: $subject,
                requesterTerritory: $requesterTerritory,
                status: AuthorizationContextStatus::INCOMPLETE,
                reason: AuthorizationContextReason::RELATIONSHIP_REQUIRED,
            );
        }

        return $this->context(
            classification: $classification,
            requester: $requester,
            subject: $subject,
            requesterTerritory: $requesterTerritory,
            status: AuthorizationContextStatus::COMPLETE,
            reason: AuthorizationContextReason::CONTEXT_COMPLETE,
        );
    }

    private function context(
        InformationAccessClassification $classification,
        RequesterIdentityContext $requester,
        ?ProtectedInformationSubject $subject,
        ?Rt $requesterTerritory,
        AuthorizationContextStatus $status,
        AuthorizationContextReason $reason,
    ): ProtectedInformationAuthorizationContext {
        return new ProtectedInformationAuthorizationContext(
            classification: $classification,
            requester: $requester,
            subject: $subject,
            requesterTerritory: $requesterTerritory,
            staffScope: $requester->staffScope,
            status: $status,
            reason: $reason,
        );
    }
}
