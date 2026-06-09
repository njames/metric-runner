<?php

namespace YourOrg\MetricRunner\Support;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Collection;
use YourOrg\MetricRunner\Exceptions\ClickHouseQueryException;

class ClickHouseClient
{
    private Client $http;
    private array  $config;

    public function __construct(array $config)
    {
        $this->config = $config;

        $scheme = $config['https'] ? 'https' : 'http';

        $this->http = new Client([
            'base_uri' => "{$scheme}://{$config['host']}:{$config['port']}",
            'timeout'  => $config['timeout'],
            'auth'     => [$config['username'], $config['password']],
        ]);
    }

    /**
     * Execute a SQL query with ClickHouse native {param:Type} bindings.
     *
     * Params are passed as query string parameters prefixed with `param_`.
     * e.g. {date_from:Date} → ?param_date_from=2024-01-01
     *
     * @param  array<string, scalar>  $params
     */
    public function query(string $sql, array $params = [], ?int $timeout = null): Collection
    {
        $query = [
            'database'                    => $this->config['database'],
            'default_format'              => 'JSONEachRow',
            'max_execution_time'          => $timeout ?? $this->config['timeout'],
            'wait_end_of_query'           => 1,
        ];

        // Bind typed params: {date_from:Date} → param_date_from=value
        foreach ($params as $key => $value) {
            $query["param_{$key}"] = $value;
        }

        try {
            $response = $this->http->post('/', [
                'query'  => $query,
                'body'   => $sql,
                'headers'=> ['Content-Type' => 'text/plain'],
            ]);
        } catch (RequestException $e) {
            $body = $e->hasResponse()
                ? (string) $e->getResponse()->getBody()
                : $e->getMessage();

            throw new ClickHouseQueryException(
                "ClickHouse query failed: {$body}",
                previous: $e
            );
        }

        $lines = array_filter(
            explode("\n", trim((string) $response->getBody()))
        );

        return collect(array_map(
            fn($line) => json_decode($line, associative: true),
            $lines
        ));
    }

    public function ping(): bool
    {
        try {
            $response = $this->http->get('/ping');
            return trim((string) $response->getBody()) === 'Ok.';
        } catch (\Throwable) {
            return false;
        }
    }
}
