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

    'qris' => [
        'mode' => env('QRIS_MODE', 'manual'),
        'base_string' => env('QRIS_BASE_STRING', env('QRIS_STRING', '00020101021126610016ID.CO.SHOPEE.WWW01189360091800235336060208235336060303UMI51440014ID.CO.QRIS.WWW0215ID10265729711860303UMI5204581753033605802ID5910Jagat Tech6015TULANGBAWANG BA61053459462070703A0163045F57')),
        'merchant_name' => env('QRIS_MERCHANT_NAME', 'JAGAT TECH - SOFTWARE'),
        'image_url' => env('QRIS_IMAGE_URL'),
    ],

    'whatsapp' => [
        'base_url' => env('GOWA_API_BASE_URL', env('WA_API_BASE_URL', 'http://localhost:3000')),
        'user'     => env('GOWA_API_USER', env('WA_API_USER', 'admin')),
        'pass'     => env('GOWA_API_PASS', env('WA_API_PASS', 'opowae')),
    ],

    'firebase' => [
        'project_id'           => env('FIREBASE_PROJECT_ID'),
        'client_email'          => env('FIREBASE_CLIENT_EMAIL'),
        'private_key'          => env('FIREBASE_PRIVATE_KEY'),
        'service_account_json' => env('FIREBASE_SERVICE_ACCOUNT_JSON'),
        'credentials'          => env('FIREBASE_CREDENTIALS'),
        'server_key'           => env('FCM_SERVER_KEY'),
    ],

    'face_engine' => [
        'url'       => env('FACE_ENGINE_URL', 'http://127.0.0.1:8000'),
        'threshold' => env('FACE_SIMILARITY_THRESHOLD', 0.68),
    ],

    'storage_server' => [
        'url'       => env('STORAGE_SERVER_URL', 'https://storage.jagattech.my.id'),
        'key'       => env('STORAGE_SERVER_KEY', '812194c6c53ee6557e088b897f5ec08abafb49a8fa5e656f5acf75f70a93145f'),
        'admin_key' => env('STORAGE_SERVER_ADMIN_KEY', env('STORAGE_SERVER_KEY', 'android-storage-secret-key')),
        'folder'    => env('STORAGE_SERVER_KBM_FOLDER', 'kbm_docs'),
    ],

];
