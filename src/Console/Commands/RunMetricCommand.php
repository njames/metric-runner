<?php

namespace Njames\MetricRunner\Console\Commands;

use Illuminate\Console\Command;
use Njames\MetricRunner\MetricRunner;

class RunMetricCommand extends Command
{
    protected $signature = 'metrics:run
                            {key : Metric key e.g. revenue.by_month}
                            {--param=* : Params as key=value e.g. --param=date_from=2024-01-01}
                            {--fresh : Bypass cache}
                            {--json : Output raw JSON}';

    protected $description = 'Execute a metric and display results';

    public function handle(MetricRunner $runner): int
    {
        $key    = $this->argument('key');
        $params = $this->parseParams($this->option('param'));

        try {
            $result = $this->option('fresh')
                ? $runner->runFresh($key, $params)
                : $runner->run($key, $params);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        if ($this->option('json')) {
            $this->line(json_encode($result->toArray(), JSON_PRETTY_PRINT));
            return self::SUCCESS;
        }

        $this->info("Metric: {$key}");
        $this->line("Rows: {$result->count()} | Time: {$result->executionMs}ms | Cache: " . ($result->fromCache ? 'HIT' : 'MISS'));
        $this->newLine();

        if ($result->rows->isEmpty()) {
            $this->warn('No rows returned.');
            return self::SUCCESS;
        }

        $headers = array_keys($result->rows->first());
        $this->table($headers, $result->rows->toArray());

        return self::SUCCESS;
    }

    private function parseParams(array $raw): array
    {
        $params = [];
        foreach ($raw as $item) {
            [$key, $value] = explode('=', $item, 2);
            $params[trim($key)] = trim($value);
        }
        return $params;
    }
}
