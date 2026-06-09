<?php

namespace YourOrg\MetricRunner\Facades;

use Illuminate\Support\Facades\Facade;
use YourOrg\MetricRunner\DTO\MetricResult;

/**
 * @method static MetricResult run(string $key, array $params = [], ?int $cacheTtl = null, ?\Illuminate\Contracts\Auth\Authenticatable $user = null)
 * @method static MetricResult runFresh(string $key, array $params = [], ?\Illuminate\Contracts\Auth\Authenticatable $user = null)
 * @method static array        catalogue(?\Illuminate\Contracts\Auth\Authenticatable $user = null)
 *
 * @see \YourOrg\MetricRunner\MetricRunner
 */
class MetricRunner extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'metric-runner';
    }
}
