<?php

return [
    'access_token' => env('MP_ACCESS_TOKEN', 'DUMMY_ACCESS_TOKEN'),
    'public_key' => env('MP_PUBLIC_KEY', 'DUMMY_PUBLIC_KEY'),
    'currency' => env('MP_CURRENCY', 'PEN'),
    'plans' => [
        'free' => 0,
        'basic' => (float) env('MP_PLAN_BASIC', 9.90),
        'pro' => (float) env('MP_PLAN_PRO', 19.90),
    ],
    'duration_months' => (int) env('MP_PLAN_DURATION_MONTHS', 1),
    'success_url' => env('MP_SUCCESS_URL', '/payments/return'),
    'failure_url' => env('MP_FAILURE_URL', '/payments/return'),
    'pending_url' => env('MP_PENDING_URL', '/payments/return'),
];
