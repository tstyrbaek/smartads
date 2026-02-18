<?php

return [
    'cron_queue_token' => env('CRON_QUEUE_TOKEN', ''),

    'allowed_ad_sizes' => [
        ['width' => 800, 'height' => 800],
        ['width' => 1080, 'height' => 1080],
        ['width' => 1080, 'height' => 1350],
        ['width' => 1200, 'height' => 628],
    ],
];
