<?php

namespace YourOrg\MetricRunner\DTO;

use Illuminate\Support\Collection;

final class MetricResult
{
    public function __construct(
        public readonly string     $key,
        public readonly Collection $rows,
        public readonly float      $executionMs,
        public readonly bool       $fromCache,
        public readonly array      $params,
    ) {}

    public function toArray(): array
    {
        return [
            'key'          => $this->key,
            'rows'         => $this->rows->toArray(),
            'execution_ms' => $this->executionMs,
            'from_cache'   => $this->fromCache,
            'params'       => $this->params,
        ];
    }

    public function count(): int
    {
        return $this->rows->count();
    }
}
