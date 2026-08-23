<?php

namespace App\Enums;

enum TrustedInboundProcessingOutcome: string
{
    case REPORT_CREATED = 'report_created';
    case DUPLICATE_ALREADY_PROCESSED = 'duplicate_already_processed';
    case PROCESSING_IN_PROGRESS = 'processing_in_progress';
    case BLOCKED = 'blocked';
    case NON_REPORT_SERVICE = 'non_report_service';
    case FAILED = 'failed';
}
