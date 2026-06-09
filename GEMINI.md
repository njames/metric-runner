# MetricRunner: Project Instructions

SQL-first analytics metric runner for Laravel + ClickHouse.

## Project Overview
MetricRunner allows data engineers to write raw SQL metrics in `.sql` files with embedded metadata (frontmatter). The package handles parsing, parameter binding, execution against ClickHouse, and result caching.

## Tech Stack
- **Framework:** Laravel 12/13
- **Database:** ClickHouse (via Guzzle/HTTP)
- **Testing:** Pest + Orchestra Testbench
- **Design Patterns:** DTOs for data transfer, Registry pattern for metric discovery.

## Key Concepts

### 1. Metric Definitions
Metrics are `.sql` files stored in the configured `metrics_path`.
- **Key:** The file path relative to the metrics directory, using dot notation (e.g., `revenue/by_month.sql` -> `revenue.by_month`).
- **Frontmatter:** A block comment at the top of the file containing YAML-like key-value pairs.

### 2. ClickHouse Integration
- Uses native ClickHouse `{name:Type}` parameter syntax.
- Parameters are passed via HTTP query strings prefixed with `param_`.
- Results are returned as a Laravel Collection of associative arrays.

### 3. Governance Lifecycle
- **Status:** `draft` (default), `review`, `approved`.
- **Enforcement:** In production, `METRIC_RUNNER_ENFORCE_APPROVAL` should be `true` to restrict execution to `approved` metrics.

## Development Standards

### Architecture
- **Surgical Edits:** When modifying, prioritize minimal changes that preserve existing patterns.
- **DTOs:** All metric data should flow through `MetricDefinition` and `MetricResult` DTOs.
- **Exceptions:** Use the `Njames\MetricRunner\Exceptions` namespace for all package-specific errors.

### Testing & Validation
- **Pest:** All new features must have corresponding Pest tests in `tests/Unit`.
- **Testbench:** Use `Orchestra\Testbench` to simulate the Laravel environment.
- **SQL Validation:** Use `php artisan metrics:validate --dry-run` to check SQL parsing without a database connection.

### Conventions
- **Naming:** PSR-12 for PHP. Snake_case for metric file names.
- **Types:** Strict typing is required for all method signatures.
- **Documentation:** Maintain the README.md and this GEMINI.md as the source of truth for features and workflows.

## Workflow
1. **Research:** Map changes to the Registry, Runner, or Client.
2. **Implement:** Update code and DTOs as needed.
3. **Verify:** Run Pest tests and validate existing metrics.
