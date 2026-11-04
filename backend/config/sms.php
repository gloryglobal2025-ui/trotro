<?php

return [
    /*
    |--------------------------------------------------------------------------
    | SMS driver
    |--------------------------------------------------------------------------
    | "log" writes messages to the application log (safe for testing).
    | Use "hubtel" or "arkesel" in production once credentials are set.
    */
    'driver' => env('SMS_DRIVER', 'log'),

    'sender' => env('SMS_SENDER', 'Trotro'),

    // Shared secret expected on inbound SMS/USSD webhooks. When empty the
    // webhooks refuse traffic instead of accepting unauthenticated commands.
    'webhook_secret' => env('SMS_WEBHOOK_SECRET'),

    'ussd_service_code' => env('SMS_USSD_SERVICE_CODE', '*713#'),

    'hubtel' => [
        'client_id' => env('HUBTEL_CLIENT_ID'),
        'client_secret' => env('HUBTEL_CLIENT_SECRET'),
        'sender' => env('HUBTEL_SENDER_ID', env('SMS_SENDER', 'Trotro')),
        'base_url' => env('HUBTEL_SMS_URL', 'https://smsc.hubtel.com/v1/messages/send'),
    ],

    'arkesel' => [
        'api_key' => env('ARKESEL_API_KEY'),
        'sender' => env('ARKESEL_SENDER_ID', env('SMS_SENDER', 'Trotro')),
        'base_url' => env('ARKESEL_SMS_URL', 'https://sms.arkesel.com/api/v2/sms/send'),
    ],
];
