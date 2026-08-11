<?php

namespace App\Enums;

enum InformationCategory: string
{
    case SERVICE_HOURS = 'service_hours';
    case PUBLIC_CONTACT = 'public_contact';
    case PUBLIC_SCHEDULE = 'public_schedule';
    case SERVICE_REQUIREMENTS = 'service_requirements';
    case SERVICE_PROCEDURE = 'service_procedure';
    case OFFICIAL_FEE = 'official_fee';
    case CITIZEN_DATA = 'citizen_data';
    case FAMILY_DATA = 'family_data';
    case PERSONAL_REPORT_STATUS = 'personal_report_status';
    case PERSONAL_LETTER_STATUS = 'personal_letter_status';
    case CENSUS_DATA = 'census_data';
    case INTERNAL_ADMINISTRATION = 'internal_administration';
    case UNKNOWN_PROTECTED = 'unknown_protected';
}
