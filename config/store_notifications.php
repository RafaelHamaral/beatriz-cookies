<?php

return [
    'recipient' => env('STORE_NOTIFICATIONS_TO'),
    'mailer' => env('STORE_NOTIFICATIONS_MAILER', 'smtp'),
];
