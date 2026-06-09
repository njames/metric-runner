<?php

namespace YourOrg\MetricRunner\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use YourOrg\MetricRunner\MetricRunnerServiceProvider;
use YourOrg\MetricRunner\Facades\MetricRunner;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            MetricRunnerServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'MetricRunner' => MetricRunner::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        // Setup default config for testing
        $app['config']->set('metric-runner.clickhouse', [
            'host'     => env('CLICKHOUSE_HOST', 'localhost'),
            'port'     => env('CLICKHOUSE_PORT', 8123),
            'database' => env('CLICKHOUSE_DATABASE', 'analytics'),
            'username' => env('CLICKHOUSE_USERNAME', 'default'),
            'password' => env('CLICKHOUSE_PASSWORD', 'secret'),
            'https'    => env('CLICKHOUSE_HTTPS', false),
            'timeout'  => 5,
        ]);

        $app['config']->set('metric-runner.metrics_path', __DIR__ . '/../../resources/sql');
    }
}
