<?php

namespace Njames\MetricRunner;

use Illuminate\Support\ServiceProvider;
use Njames\MetricRunner\Console\Commands\ListMetricsCommand;
use Njames\MetricRunner\Console\Commands\RunMetricCommand;
use Njames\MetricRunner\Console\Commands\ValidateMetricsCommand;
use Njames\MetricRunner\Support\ClickHouseClient;
use Njames\MetricRunner\Support\MetricRegistry;

class MetricRunnerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/metric-runner.php',
            'metric-runner'
        );

        $this->app->singleton(ClickHouseClient::class, function ($app) {
            return new ClickHouseClient(
                $app['config']['metric-runner.clickhouse']
            );
        });

        $this->app->singleton(MetricRegistry::class, function ($app) {
            return new MetricRegistry(
                $app['config']['metric-runner.metrics_path']
            );
        });

        $this->app->singleton(MetricRunner::class, function ($app) {
            return new MetricRunner(
                registry: $app->make(MetricRegistry::class),
                client:   $app->make(ClickHouseClient::class),
                config:   $app['config']['metric-runner'],
            );
        });

        $this->app->alias(MetricRunner::class, 'metric-runner');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/metric-runner.php' => config_path('metric-runner.php'),
            ], 'metric-runner-config');

            $this->publishes([
                __DIR__ . '/../database/migrations/' => database_path('migrations'),
            ], 'metric-runner-migrations');

            $this->commands([
                ListMetricsCommand::class,
                RunMetricCommand::class,
                ValidateMetricsCommand::class,
            ]);
        }
    }
}
