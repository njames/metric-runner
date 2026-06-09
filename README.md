# MetricRunner

SQL-first analytics metric runner for Laravel + ClickHouse. Your data engineers write raw SQL, your PHP stays clean.

## Installation

```bash
composer require your-org/metric-runner
php artisan vendor:publish --tag=metric-runner-config
```

## Setup

Add to `.env`:

```env
CLICKHOUSE_HOST=localhost
CLICKHOUSE_PORT=8123
CLICKHOUSE_DATABASE=analytics
CLICKHOUSE_USERNAME=default
CLICKHOUSE_PASSWORD=secret
```

Create your metrics directory (default: `resources/metrics/`):

```
resources/metrics/
├── revenue/
│   ├── by_month.sql
│   └── by_product.sql
└── customers/
    └── cohort_retention.sql
```

## Writing Metrics

Each `.sql` file is one metric. Frontmatter is a block comment at the top:

```sql
/*
 * name: Revenue by Month
 * description: Total order revenue grouped by calendar month
 * status: approved
 * params: date_from, date_to
 * roles: analyst, admin
 * cache_ttl: 600
 */

SELECT
    toYYYYMM(created_at) AS month,
    sum(amount)           AS revenue,
    count()               AS order_count
FROM orders
WHERE created_at >= {date_from:Date}
  AND created_at <  {date_to:Date}
GROUP BY month
ORDER BY month ASC
```

### Frontmatter fields

| Field | Required | Description |
|---|---|---|
| `name` | No | Human-readable name (defaults to key) |
| `description` | No | What this metric measures |
| `status` | No | `draft` / `review` / `approved` (defaults to `draft`) |
| `params` | No | Comma-separated list of expected parameters |
| `roles` | No | Comma-separated roles that can execute this metric |
| `cache_ttl` | No | Cache TTL in seconds (overrides global default) |
| `timeout` | No | Query timeout in seconds (overrides global default) |

### Parameters

Use ClickHouse native `{param:Type}` syntax — types are enforced by ClickHouse, not PHP:

```sql
WHERE created_at >= {date_from:Date}
  AND customer_id  = {customer_id:UInt64}
  AND status       = {status:String}
```

## Usage in PHP

```php
use YourOrg\MetricRunner\Facades\MetricRunner;

// Basic usage — key is directory.filename
$result = MetricRunner::run('revenue.by_month', [
    'date_from' => '2024-01-01',
    'date_to'   => '2024-12-31',
]);

// $result->rows is a Laravel Collection of associative arrays
foreach ($result->rows as $row) {
    echo "{$row['month']}: {$row['revenue']}";
}

// Bypass cache for this call
$result = MetricRunner::runFresh('revenue.by_month', $params);

// With per-call cache TTL override
$result = MetricRunner::run('revenue.by_month', $params, cacheTtl: 60);

// With auth check (uses your role_check_callback from config)
$result = MetricRunner::run('revenue.by_month', $params, user: $request->user());

// List available metrics for the current user
$catalogue = MetricRunner::catalogue($request->user());
```

### In a controller

```php
public function revenueByMonth(Request $request): JsonResponse
{
    $validated = $request->validate([
        'date_from' => 'required|date',
        'date_to'   => 'required|date|after:date_from',
    ]);

    $result = MetricRunner::run('revenue.by_month', $validated, user: $request->user());

    return response()->json($result->toArray());
}
```

### In Livewire

```php
public function loadMetric(): void
{
    $this->rows = MetricRunner::run('revenue.by_month', [
        'date_from' => $this->dateFrom,
        'date_to'   => $this->dateTo,
    ])->rows->toArray();
}
```

## Artisan Commands

```bash
# List all metrics
php artisan metrics:list
php artisan metrics:list --status=approved

# Run a metric from the CLI
php artisan metrics:run revenue.by_month --param=date_from=2024-01-01 --param=date_to=2024-12-31
php artisan metrics:run revenue.by_month --param=date_from=2024-01-01 --param=date_to=2024-12-31 --json
php artisan metrics:run revenue.by_month --param=date_from=2024-01-01 --param=date_to=2024-12-31 --fresh

# Validate all metric SQL files
php artisan metrics:validate
php artisan metrics:validate --dry-run   # parse only, no CH connection required
```

## Governance Workflow

Metrics follow a `draft → review → approved` lifecycle. In production, only `approved` metrics execute (configurable via `enforce_approval` in config).

Typical workflow:
1. Data engineer creates a `.sql` file with `status: draft`
2. Opens a PR for review
3. Reviewer sets `status: review`, comments on SQL
4. Once approved, set `status: approved` and merge
5. Metric is now live

## Role-Based Access

Restrict metrics to specific roles:

```sql
/* roles: admin, finance */
SELECT ...
```

Configure the role check in `config/metric-runner.php`:

```php
// Spatie Permission
'role_check_callback' => fn($user, $roles) => $user->hasAnyRole($roles),

// Custom
'role_check_callback' => fn($user, $roles) => in_array($user->team_role, $roles),
```
