<?php

namespace Njames\MetricRunner\Console\Commands;

use Illuminate\Console\Command;
use Njames\MetricRunner\Support\MetricRegistry;

class ListMetricsCommand extends Command
{
    protected $signature   = 'metrics:list {--status= : Filter by status (draft|review|approved)}';
    protected $description = 'List all registered metric definitions';

    public function handle(MetricRegistry $registry): int
    {
        $metrics = $registry->all();

        $statusFilter = $this->option('status');

        if ($statusFilter) {
            $metrics = array_filter($metrics, fn($m) => $m->status === $statusFilter);
        }

        if (empty($metrics)) {
            $this->info('No metrics found.');
            return self::SUCCESS;
        }

        $rows = array_map(fn($m) => [
            $m->key,
            $m->name,
            $m->status,
            empty($m->roles) ? '(all)' : implode(', ', $m->roles),
            $m->cacheTtl !== null ? "{$m->cacheTtl}s" : 'default',
        ], $metrics);

        $this->table(
            ['Key', 'Name', 'Status', 'Roles', 'Cache TTL'],
            $rows
        );

        return self::SUCCESS;
    }
}
