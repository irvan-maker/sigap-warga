<?php

namespace App\Enums;

enum IntentRule: string
{
    case EMERGENCY_DIRECT_AMBULANCE_REQUEST = 'emergency_direct_ambulance_request';
    case EMERGENCY_PERSON_UNCONSCIOUS = 'emergency_person_unconscious';
    case EMERGENCY_FIRE = 'emergency_fire';
    case EMERGENCY_SEVERE_ACCIDENT = 'emergency_severe_accident';
    case EMERGENCY_MEDICAL_HELP = 'emergency_medical_help';
    case EMERGENCY_BREATHING_DIFFICULTY = 'emergency_breathing_difficulty';
    case EMERGENCY_SAFETY_THREAT = 'emergency_safety_threat';
    case INFORMATION_AMBULANCE_CONTACT = 'information_ambulance_contact';
    case INFORMATION_AMBULANCE_SCHEDULE = 'information_ambulance_schedule';
    case INFORMATION_AMBULANCE_REQUIREMENTS = 'information_ambulance_requirements';
    case INFORMATION_AMBULANCE_COST = 'information_ambulance_cost';
    case INFORMATION_SERVICE_HOURS = 'information_service_hours';
    case INFORMATION_LETTER_REQUIREMENTS = 'information_letter_requirements';
    case INFORMATION_POSYANDU_SCHEDULE = 'information_posyandu_schedule';
    case INFORMATION_PUBLIC_CONTACT = 'information_public_contact';
    case LETTER_DOMICILE = 'letter_domicile';
    case LETTER_INTRODUCTION = 'letter_introduction';
    case LETTER_BUSINESS = 'letter_business';
    case LETTER_CERTIFICATE = 'letter_certificate';
    case LETTER_MARRIAGE_INTRODUCTION = 'letter_marriage_introduction';
    case ASPIRATION_PROPOSAL = 'aspiration_proposal';
    case ASPIRATION_SUGGESTION = 'aspiration_suggestion';
    case REPORT_HIGH_FALLEN_TREE = 'report_high_fallen_tree';
    case REPORT_HIGH_LANDSLIDE = 'report_high_landslide';
    case REPORT_HIGH_FLOOD = 'report_high_flood';
    case REPORT_HIGH_UTILITY_POLE = 'report_high_utility_pole';
    case REPORT_ROAD_DAMAGE = 'report_road_damage';
    case REPORT_STREET_LIGHT = 'report_street_light';
    case REPORT_GARBAGE = 'report_garbage';
    case REPORT_DRAINAGE = 'report_drainage';
    case REPORT_PUBLIC_FACILITY = 'report_public_facility';
    case UNKNOWN_NO_MATCH = 'unknown_no_match';
}
