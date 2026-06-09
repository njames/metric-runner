<?php

namespace Njames\MetricRunner;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Cache;
use Njames\MetricRunner\DTO\MetricDefinition;
use Njames\MetricRunner\DTO\MetricResult;
use Njames\MetricRunner\Exceptions\MetricAccessDeniedException;
use Njames\MetricRunner\Exceptions\MetricNotApprovedException;
use Njames\MetricRunner\Support\ClickHouseClient;
use Njames\MetricRunner\Support\MetricRegistry;

class MetricRunner
{
    public function __construct(
        private readonly MetricRegistry  $registry,
        private readonly ClickHouseClient $client,
        private readonly array            $config,
    ) {}

    /**
     * Execute a metric by key.
     *
     * @param  array<string, scalar>  $params
     */
    public function run(
        string            $key,
        array             $params       = [],
        ?int              $cacheTtl     = null,
        ?Authenticatable  $user         = null,
    ): MetricResult {
        $metric = $this->registry->get($key);

        $this->authorise($metric, $user);

        $ttl = $cacheTtl ?? $metric->cacheTtl ?? $this->config['cache_ttl'];

        if ($ttl > 0) {
            return $this->runCached($metric, $params, $ttl);
        }

        return $this->execute($metric, $params);
    }

    /**
     * Execute without cache regardless of metric settings.
     *
     * @param  array<string, scalar>  $params
     */
    public function runFresh(
        string           $key,
        array            $params = [],
        ?Authenticatable $user   = null,
    ): MetricResult {
        return $this->run($key, $params, cacheTtl: 0, user: $user);
    }

    /**
     * List all metrics visible to the given user.
     *
     * @return array<string, MetricDefinition>
     */
    public function catalogue(?Authenticatable $user = null): array
    {
        $all = $this->config['enforce_approval']
            ? $this->registry->approved()
            : $this->registry->all();

        if ($user === null) {
            return $all;
        }

        return array_filter(
            $all,
            fn($metric) => $this->userCanAccess($metric, $user)
        );
    }

    private function runCached(MetricDefinition $metric, array $params, int $ttl): MetricResult
    {
        $cacheKey = $this->cacheKey($metric->key, $params);
        $store    = $this->config['cache_store'];

        $cached = Cache::store($store)->get($cacheKey);

        if ($cached !== null) {
            return new MetricResult(
                key:         $metric->key,
                rows:        collect($cached['rows']),
                executionMs: 0,
                fromCache:   true,
                params:      $params,
            );
        }

        $result = $this->execute($metric, $params);

        Cache::store($store)->put($cacheKey, [
            'rows' => $result->rows->toArray(),
        ], $ttl);

        return $result;
    }

    private function execute(MetricDefinition $metric, array $params): MetricResult
    {
        $start = hrtime(true);

        $rows = $this->client->query(
            sql:     $metric->sql,
            params:  $params,
            timeout: $metric->timeout ?? $this->config['query_timeout'],
        );

        $ms = (hrtime(true) - $start) / 1e6;

        return new MetricResult(
            key:         $metric->key,
            rows:        $rows,
            executionMs: round($ms, 2),
            fromCache:   false,
            params:      $params,
        );
    }

    private function authorise(MetricDefinition $metric, ?Authenticatable $user): void
    {
        if ($this->config['enforce_approval'] && !$metric->isApproved()) {
            throw new MetricNotApprovedException(
                "Metric [{$metric->key}] is not approved for execution."
            );
        }

        if (!empty($metric->roles) && !$this->userCanAccess($metric, $user)) {
            throw new MetricAccessDeniedException(
                "Access denied to metric [{$metric->key}]."
            );
        }
    }

    private function userCanAccess(MetricDefinition $metric, ?Authenticatable $user): bool
    {
        if (empty($metric->roles)) return true;
        if ($user === null)        return false;

        $callback = $this->config['role_check_callback'];

        if (is_callable($callback)) {
            return (bool) $callback($user, $metric->roles);
        }

        // Fallback: check for hasAnyRole (Spatie Permission compatible)
        if (method_exists($user, 'hasAnyRole')) {
            return $user->hasAnyRole($metric->roles);
        }

        return false;
    }

    private function cacheKey(string $metricKey, array $params): string
    {
        return 'metric_runner:' . $metricKey . ':' . md5(serialize($params));
    }
}
