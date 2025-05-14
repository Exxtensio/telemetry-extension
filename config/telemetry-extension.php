<?php

return [
    'default' => env('TELEMETRY_SERVICE_NAME', 'default'),

    'services' => [
        'app' => [
            'name' => 'app',
        ],
        'api-core' => [
            'name' => 'api-core',
        ],
        'data-core' => [
            'name' => 'data-core',
        ],
        'db-core' => [
            'name' => 'db-core',
        ],
        'batch-core' => [
            'name' => 'batch-core',
        ],
    ]
];
