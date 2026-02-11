<?php

return [

    /*
    |--------------------------------------------------------------------------
    | MLflow Tracking URI
    |--------------------------------------------------------------------------
    |
    | The URI of your MLflow tracking server. This is where all experiments,
    | runs, models, and artifacts will be stored and tracked.
    |
    */

    'tracking_uri' => env('MLFLOW_TRACKING_URI', 'http://localhost:5000'),

    /*
    |--------------------------------------------------------------------------
    | Default Experiment
    |--------------------------------------------------------------------------
    |
    | The default experiment ID to use when none is specified. Leave null
    | to require explicit experiment specification.
    |
    */

    'default_experiment' => env('MLFLOW_DEFAULT_EXPERIMENT'),

    /*
    |--------------------------------------------------------------------------
    | HTTP Client Options
    |--------------------------------------------------------------------------
    |
    | Configuration options for the underlying HTTP client (Guzzle).
    | You can adjust timeouts, retries, and other connection settings here.
    |
    */

    'options' => [
        'timeout' => (float) env('MLFLOW_TIMEOUT', 60.0),
        'connect_timeout' => (float) env('MLFLOW_CONNECT_TIMEOUT', 10.0),
        'max_retries' => (int) env('MLFLOW_MAX_RETRIES', 3),
        'retry_delay' => (float) env('MLFLOW_RETRY_DELAY', 1.0),
        'verify' => env('MLFLOW_VERIFY_SSL', true),
        'proxy' => env('MLFLOW_PROXY'),
        'debug' => env('MLFLOW_DEBUG', false),
        'headers' => [
            'Authorization' => env('MLFLOW_API_TOKEN')
                ? 'Bearer ' . env('MLFLOW_API_TOKEN')
                : null,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Caching
    |--------------------------------------------------------------------------
    |
    | Enable caching for MLflow API responses to reduce API calls and improve
    | performance. Uses Laravel's cache system.
    |
    */

    'cache' => [
        'enabled' => env('MLFLOW_CACHE_ENABLED', false),
        'ttl' => (int) env('MLFLOW_CACHE_TTL', 300), // 5 minutes
        'store' => env('MLFLOW_CACHE_STORE', config('cache.default')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Enable logging for MLflow API requests and responses. Useful for
    | debugging and monitoring. Uses Laravel's logging system.
    |
    */

    'logging' => [
        'enabled' => env('MLFLOW_LOGGING_ENABLED', false),
        'channel' => env('MLFLOW_LOGGING_CHANNEL', config('logging.default')),
    ],

];
