<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'sprinkle' => [
        'admin_auth_key' => env('ADMIN_AUTH_KEY'),
        'quote_notification_email' => env('QUOTE_NOTIFICATION_EMAIL', 'brettj@dekode.co.nz'),
        'quote_confirmed_notification_email' => env('QUOTE_CONFIRMED_NOTIFICATION_EMAIL', env('QUOTE_NOTIFICATION_EMAIL', 'brettj@dekode.co.nz')),
        'quote_reschedule_notification_email' => env('QUOTE_RESCHEDULE_NOTIFICATION_EMAIL', env('QUOTE_NOTIFICATION_EMAIL', 'brettj@dekode.co.nz')),
        'testimonial_notification_email' => env('TESTIMONIAL_NOTIFICATION_EMAIL', env('QUOTE_NOTIFICATION_EMAIL', 'brettj@dekode.co.nz')),
        'quote_admin_copy_email' => env('QUOTE_ADMIN_COPY_EMAIL'),
        'quote_link_expiry_days' => env('QUOTE_LINK_EXPIRY_DAYS', 45),
        'geoip_endpoint' => env('GEOIP_ENDPOINT'),
        'geoip_token' => env('GEOIP_TOKEN'),
        'geoip_timeout_seconds' => env('GEOIP_TIMEOUT_SECONDS', 2),
        'geoip_cache_minutes' => env('GEOIP_CACHE_MINUTES', 1440),
    ],

];
