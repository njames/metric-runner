<?php

namespace Njames\MetricRunner\Facades;

use Illuminate\Support\Facades\Facade;
use Njames\MetricRunner\DTO\MetricResult;

/**
 * @method static MetricResult run(string $key, array $params = [], ?int $cacheTtl = null, ?\Illuminate\Contracts\Auth\Authenticatable $user = null)
 * @method static MetricResult runFresh(string $key, array $params = [], ?\Illuminate\Contracts\Auth\Authenticatable $user = null)
 * @method static array        catalogue(?\Illuminate\Contracts\Auth\Authenticatable $user = null)
 *
 * @see \Njames\MetricRunner\MetricRunner
 */
class MetricRunner extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'metric-runner';
    }
}
