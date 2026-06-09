<?php

namespace YourOrg\MetricRunner\Console\Commands;

use Illuminate\Console\Command;
use YourOrg\MetricRunner\Support\ClickHouseClient;
use YourOrg\MetricRunner\Support\MetricRegistry;

class ValidateMetricsCommand extends Command
{
    protected $signature   = 'metrics:validate {--dry-run : Parse only, do not hit ClickHouse}';
    protected $description = 'Validate all metric SQL files for parse errors and ClickHouse syntax';

    public function handle(MetricRegistry $registry, ClickHouseClient $client): int
    {
        $metrics  = $registry->all();
        $failed   = 0;
        $dryRun   = $this->option('dry-run');

        if (empty($metrics)) {
            $this->info('No metrics to validate.');
            return self::SUCCESS;
        }

        foreach ($metrics as $key => $metric) {
            try {
                // Always validate parse (MetricDefinition constructor already did this)
                // For non-dry-run, ask ClickHouse to explain the query without running it
                if (!$dryRun) {
                    $client->query(
                        "EXPLAIN SYNTAX\n" . $metric->sql,
                        // Pass zeroed params so types satisfy the parser
                        array_fill_keys(
                            array_column($metric->params, 'name') ?: array_keys($metric->params),
                            ''
                        )
                    );
                }

                $this->line("<fg=green>✔</> {$key} ({$metric->status})");
            } catch (\Throwable $e) {
                $this->line("<fg=red>✘</> {$key}: {$e->getMessage()}");
                $failed++;
            }
        }

        $this->newLine();
        $total = count($metrics);
        $pass  = $total - $failed;
        $this->info("{$pass}/{$total} metrics valid.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
