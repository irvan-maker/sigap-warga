<?php

namespace App\Enums;

/**
 * A conceptual service action only; no case or workflow is executed by it.
 */
enum ServiceActionType: string
{
    case CREATE_CASE = 'create_case';
    case PROVIDE_INFORMATION = 'provide_information';
    case INITIATE_ADMINISTRATIVE_SERVICE = 'initiate_administrative_service';
    case REGISTER_ASPIRATION = 'register_aspiration';
    case INITIATE_EMERGENCY_RESPONSE = 'initiate_emergency_response';
    case REQUEST_CLARIFICATION = 'request_clarification';
}
