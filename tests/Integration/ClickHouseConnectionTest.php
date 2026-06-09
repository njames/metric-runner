<?php

namespace Njames\MetricRunner\Tests\Integration;

use Njames\MetricRunner\Tests\TestCase;
use Njames\MetricRunner\Support\ClickHouseClient;

class ClickHouseConnectionTest extends TestCase
{
    /** @test */
    public function it_can_connect_to_clickhouse()
    {
        /** @var ClickHouseClient $client */
        $client = app(ClickHouseClient::class);

        $this->assertTrue($client->ping(), 'Could not connect to ClickHouse. Is the Docker container running?');
    }

    /** @test */
    public function it_can_query_the_orders_table()
    {
        /** @var ClickHouseClient $client */
        $client = app(ClickHouseClient::class);

        $results = $client->query('SELECT count() as count FROM orders');

        $this->assertCount(1, $results);
        $this->assertGreaterThan(0, $results->first()['count']);
    }
}
