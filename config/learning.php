<?php

return [
    'weak' => [
        'min_answers'  => 5,
        'window_days'  => 90,
        'threshold'    => 70, // Weak topic is score < 70
        'priority'     => [
            'high'   => 40, // < 40 is high priority
            'medium' => 60, // 40-59 is medium priority
            'low'    => 70, // 60-69 is low priority
        ],
    ],
    'minutes' => [
        'high'   => 45,
        'medium' => 30,
        'low'    => 20,
    ],
];
