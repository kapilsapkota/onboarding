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
    'offered_services' => [
        'Domain Management',
        'Website Hosting',
        'Office 365 Licensing',
        'Google Workspace Licensing',
        'Adobe Licensing',
        'Dropbox Licensing',
        'Graphic Design',
        'Website Design & Development',
        'Printing Services',
        'Online Marketing',
        'Hardware & Software',
        'Managed IT Services',
        'Uniforms',
        'Signage',
        'Professional Services',
        'Cyber Security',
        'Telecommunication Services',
        'Internet Data Services',
        'Cloud Based Solutions',
        'Audio Visual',
        'Photography & Videography',
        'AI Development & Automation',
    ],
    'stripe' => [
        'key'    => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

];
