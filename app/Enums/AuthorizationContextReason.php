<?php

namespace App\Enums;

enum AuthorizationContextReason: string
{
    case CONTEXT_COMPLETE = 'context_complete';
    case REQUESTER_REQUIRED = 'requester_required';
    case SUBJECT_REQUIRED = 'subject_required';
    case RELATIONSHIP_REQUIRED = 'relationship_required';
    case PUBLIC_NOT_APPLICABLE = 'public_not_applicable';
}
