<?php

namespace App\Enums;

enum TrustedInboundProcessingReason: string
{
    case REPORT_CREATED = 'report_created';
    case INBOUND_ALREADY_SUCCEEDED = 'inbound_already_succeeded';
    case ELIGIBILITY_BLOCKED = 'eligibility_blocked';
    case SERVICE_NOT_EXECUTED = 'service_not_executed';
    case PROCESSING_EXCEPTION = 'processing_exception';
}
