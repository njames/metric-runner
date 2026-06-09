<?php

use YourOrg\MetricRunner\DTO\MetricDefinition;

it('parses frontmatter from a sql file', function () {
    $tmpFile = tempnam(sys_get_temp_dir(), 'metric_') . '.sql';

    file_put_contents($tmpFile, <<<SQL
    /*
     * name: Revenue by Month
     * description: Monthly revenue totals
     * status: approved
     * params: date_from, date_to
     * roles: analyst, admin
     * cache_ttl: 600
     */

    SELECT sum(amount) FROM orders
    WHERE created_at >= {date_from:Date}
    SQL);

    $def = MetricDefinition::fromFile($tmpFile, 'revenue.by_month');

    expect($def->key)->toBe('revenue.by_month')
        ->and($def->name)->toBe('Revenue by Month')
        ->and($def->status)->toBe('approved')
        ->and($def->roles)->toBe(['analyst', 'admin'])
        ->and($def->cacheTtl)->toBe(600)
        ->and($def->sql)->toContain('SELECT sum(amount)');

    unlink($tmpFile);
});

it('defaults status to draft when not specified', function () {
    $tmpFile = tempnam(sys_get_temp_dir(), 'metric_') . '.sql';

    file_put_contents($tmpFile, 'SELECT 1');

    $def = MetricDefinition::fromFile($tmpFile, 'test.metric');

    expect($def->status)->toBe('draft')
        ->and($def->isApproved())->toBeFalse();

    unlink($tmpFile);
});

it('handles files with no frontmatter', function () {
    $tmpFile = tempnam(sys_get_temp_dir(), 'metric_') . '.sql';

    file_put_contents($tmpFile, 'SELECT count() FROM orders');

    $def = MetricDefinition::fromFile($tmpFile, 'orders.count');

    expect($def->sql)->toBe('SELECT count() FROM orders')
        ->and($def->params)->toBe([])
        ->and($def->roles)->toBe([]);

    unlink($tmpFile);
});
