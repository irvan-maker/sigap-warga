<?php

return [
    'quick_report' => [
        'enabled' => env('MODULE_QUICK_REPORT_ENABLED', true),
        'status' => 'PILOT',
        'label' => 'Laporan Cepat',
    ],
    'census' => [
        'enabled' => env('MODULE_CENSUS_ENABLED', true),
        'status' => 'PROTOTYPE',
        'label' => 'Sensus Warga',
    ],
    'posyandu' => [
        'enabled' => env('MODULE_POSYANDU_ENABLED', true),
        'status' => 'PROTOTYPE',
        'label' => 'Posyandu',
    ],
    'letters' => [
        'enabled' => env('MODULE_LETTERS_ENABLED', true),
        'status' => 'PROTOTYPE',
        'label' => 'Pelayanan Surat',
    ],
    'emergency' => [
        'enabled' => env('MODULE_EMERGENCY_ENABLED', false),
        'status' => 'SAFETY_PROTOTYPE',
        'label' => 'Handoff Darurat',
    ],
];
