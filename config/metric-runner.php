<?php

return [

    /*
    |--------------------------------------------------------------------------
    | ClickHouse Connection
    |--------------------------------------------------------------------------
    | Connection details for your ClickHouse instance. These can be overridden
    | per-metric in the metric definition's 'connection' key.
    */
    'clickhouse' => [
        'host'     => env('CLICKHOUSE_HOST', 'localhost'),
        'port'     => env('CLICKHOUSE_PORT', 8123),
        'database' => env('CLICKHOUSE_DATABASE', 'default'),
        'username' => env('CLICKHOUSE_USERNAME', 'default'),
        'password' => env('CLICKHOUSE_PASSWORD', ''),
        'https'    => env('CLICKHOUSE_HTTPS', false),
        'timeout'  => env('CLICKHOUSE_TIMEOUT', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Metric Definitions Path
    |--------------------------------------------------------------------------
    | Directory where .sql metric files live. Each file is one metric.
    | Subdirectories are supported — they become part of the metric key.
    | e.g. metrics/revenue/by_month.sql → key: revenue.by_month
    */
    'metrics_path' => resource_path('metrics'),

    /*
    |--------------------------------------------------------------------------
    | Default Cache Time To Live (TTL)
    |--------------------------------------------------------------------------
    | Default result cache TTL in seconds. Set to 0 to disable caching.
    | Can be overridden per-metric or per-call.
    */
    'cache_ttl' => env('METRIC_RUNNER_CACHE_TTL', 300),

    /*
    |--------------------------------------------------------------------------
    | Cache Store
    |--------------------------------------------------------------------------
    | Which Laravel cache store to use for metric result caching.
    */
    'cache_store' => env('METRIC_RUNNER_CACHE_STORE', 'redis'),

    /*
    |--------------------------------------------------------------------------
    | Governance
    |--------------------------------------------------------------------------
    | When enforce_approval is true, only metrics with status 'approved'
    | can be executed in production. Disable for local dev if needed.
    */
    'enforce_approval' => env('METRIC_RUNNER_ENFORCE_APPROVAL', true),

    /*
    |--------------------------------------------------------------------------
    | Query Timeout
    |--------------------------------------------------------------------------
    | Maximum query execution time in seconds. ClickHouse will cancel
    | queries that exceed this. Can be overridden per-metric.
    */
    'query_timeout' => env('METRIC_RUNNER_QUERY_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Roles
    |--------------------------------------------------------------------------
    | Map Laravel roles/gates to metric visibility. Metrics can declare a
    | 'roles' array in their frontmatter — only users with matching roles
    | will be able to execute those metrics.
    */
    'role_check_callback' => null, // e.g. fn($user, $roles) => $user->hasAnyRole($roles)

];
