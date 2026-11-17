<?php

return [
    'notice' => [
        'enabled' => filter_var(env('STUDENT_NOTICE', false), FILTER_VALIDATE_BOOLEAN),
        'url'     => env('STUDENT_NOTICE_URL', null),
    ],
];
