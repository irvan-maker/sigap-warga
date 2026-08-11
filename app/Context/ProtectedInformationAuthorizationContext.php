<?php

namespace App\Context;

use App\Enums\AuthorizationContextReason;
use App\Enums\AuthorizationContextStatus;
use App\Models\Rt;

/**
 * Facts for a future authorization policy. This is not an allow/deny result.
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
