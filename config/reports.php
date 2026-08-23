<?php

return [
    'sla' => [
        'NORMAL' => [
            'response_minutes' => (int) env('REPORT_SLA_NORMAL_RESPONSE_MINUTES', 120),
            'resolution_minutes' => (int) env('REPORT_SLA_NORMAL_RESOLUTION_MINUTES', 4320),
        ],
        'HIGH' => [
            'response_minutes' => (int) env('REPORT_SLA_HIGH_RESPONSE_MINUTES', 15),
            'resolution_minutes' => (int) env('REPORT_SLA_HIGH_RESOLUTION_MINUTES', 240),
        ],
        'EMERGENCY' => [
            'response_minutes' => (int) env('REPORT_SLA_EMERGENCY_RESPONSE_MINUTES', 5),
            'resolution_minutes' => (int) env('REPORT_SLA_EMERGENCY_RESOLUTION_MINUTES', 60),
        ],
    ],
];
