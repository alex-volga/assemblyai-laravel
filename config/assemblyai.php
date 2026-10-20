<?php

declare(strict_types=1);

use AssemblyAI\Config;

return [
    /*
    |--------------------------------------------------------------------------
    | AssemblyAI API Key
    |--------------------------------------------------------------------------
    |
    | Your AssemblyAI API key. You can find this in your AssemblyAI dashboard:
    | https://www.assemblyai.com/app/account
    |
    */
    'api_key' => env('ASSEMBLYAI_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Base URL
    |--------------------------------------------------------------------------
    |
    | The base URL for the AssemblyAI API. For European Union data residency,
    | use Config::EU_BASE_URL ('https://api.eu.assemblyai.com').
    |
    */
    'base_url' => env('ASSEMBLYAI_BASE_URL', Config::DEFAULT_BASE_URL),

    /*
    |--------------------------------------------------------------------------
    | HTTP Request Timeout
    |--------------------------------------------------------------------------
    |
    | The timeout in seconds for individual HTTP requests to AssemblyAI.
    |
    */
    'timeout' => (float) env('ASSEMBLYAI_TIMEOUT', Config::DEFAULT_TIMEOUT),

    /*
    |--------------------------------------------------------------------------
    | Polling Interval & Timeout
    |--------------------------------------------------------------------------
    |
    | The interval in seconds between status polls when waiting for transcript
    | completion, and the total timeout in seconds before throwing a TimeoutException.
    |
    */
    'poll_interval' => (int) env('ASSEMBLYAI_POLL_INTERVAL', Config::DEFAULT_POLL_INTERVAL),
    'poll_timeout' => (int) env('ASSEMBLYAI_POLL_TIMEOUT', Config::DEFAULT_POLL_TIMEOUT),

    /*
    |--------------------------------------------------------------------------
    | Custom Request Headers
    |--------------------------------------------------------------------------
    |
    | Additional HTTP headers to be sent with every request to the API.
    |
    */
    'headers' => [],

    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    |
    | Authentication settings for AssemblyAI webhook callbacks.
    | AssemblyAI attaches your custom header and secret to verify authenticity.
    |
    */
    'webhook' => [
        'header' => env('ASSEMBLYAI_WEBHOOK_HEADER', 'X-Webhook-Secret'),
        'secret' => env('ASSEMBLYAI_WEBHOOK_SECRET'),
    ],
];
