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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'bornpadel' => [
        'api_url' => env('BORNPADEL_API_URL', env('APP_ENV') === 'local'
            ? 'http://localhost/bornpadel/api/v1/external'
            : 'https://bornpadel.net/api/v1/external'),
        'api_token' => env('BORNPADEL_API_TOKEN', '91972885-619d-491b-905d-429b51691214'),
        'public_url' => env('BORNPADEL_PUBLIC_URL', env('APP_ENV') === 'local'
            ? 'http://localhost/bornpadel'
            : 'https://bornpadel.net'),
        'public_path' => env('BORNPADEL_PUBLIC_PATH'),
    ],

];
