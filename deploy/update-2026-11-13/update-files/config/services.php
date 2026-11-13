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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    // The fingerprint matcher in tools/fingerprint-matcher, for USB readers such as the U.are.U 5100.
    'fingerprint' => [
        // server: PHP calls the helper (a VPS, or XAMPP with the helper on the same computer).
        // local: the browser on each school computer calls the helper running on that computer (shared hosting).
        'mode' => env('FINGERPRINT_MATCHER_MODE', 'server'),
        'local_url' => env('FINGERPRINT_LOCAL_URL', 'http://127.0.0.1:5100'),
        // The Windows helper the admin can download, built by: dotnet publish (see tools/fingerprint-matcher/README.md).
        'helper_exe' => env('FINGERPRINT_HELPER_EXE', base_path('tools/fingerprint-matcher/dist/FingerprintHelper.exe')),
        'url' => env('FINGERPRINT_MATCHER_URL', 'http://127.0.0.1:5100'),
        'key' => env('FINGERPRINT_MATCHER_KEY'),
        // SourceAFIS score needed to accept a match. 40 is the usual starting point; higher is stricter.
        'threshold' => (float) env('FINGERPRINT_MATCH_THRESHOLD', 40),
        // The U.are.U 5100 scans at 500 dots per inch.
        'dpi' => (float) env('FINGERPRINT_READER_DPI', 500),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],


    // The salesman / owner phone app (see /sales). One Play Store app serves every business.
    'sales_app' => [
        'name' => env('SALES_APP_NAME', 'MSK Devs Sales'),
        'short_name' => env('SALES_APP_SHORT_NAME', 'MSK Sales'),
        // For the Play Store app: its package name and signing key fingerprint(s), comma separated.
        'android_package' => env('SALES_APP_ANDROID_PACKAGE', 'com.mskdevs.sales'),
        'android_sha256' => env('SALES_APP_ANDROID_SHA256'),
    ],

    /*
     * Automatic messages are set up in the owner panel → Messaging (your SMS gateway phone, an SMS or
     * WhatsApp company's API, or Meta's WhatsApp API). Here only the time of the daily reminders.
     */
    'sms' => [
        'reminder_time' => env('SMS_REMINDER_TIME', '09:00'),
    ],
];
