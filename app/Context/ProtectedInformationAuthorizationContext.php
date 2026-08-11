<?php

namespace App\Context;

use App\Enums\AuthorizationContextReason;
use App\Enums\AuthorizationContextStatus;
use App\Models\Rt;

/**
 * Facts for a future authorization policy. This is not an allow/deny result.
 *
 * This context may hold Citizen, User, and Rt model references. It is an
 * internal domain object and must never be serialized directly to a response.
 */
final readonly class ProtectedInformationAuthorizationContext
{
    public function __construct(
        public InformationAccessClassification $classification,
        public RequesterIdentityContext $requester,
        public ?ProtectedInformationSubject $subject,
        public ?Rt $requesterTerritory,
        public ?StaffScopeContext $staffScope,
        public AuthorizationContextStatus $status,
        public AuthorizationContextReason $reason,
    ) {}
}
