<?php

namespace Njames\MetricRunner\Support;

use Illuminate\Support\Str;
use Symfony\Component\Finder\Finder;
use Njames\MetricRunner\DTO\MetricDefinition;
use Njames\MetricRunner\Exceptions\MetricNotFoundException;

class MetricRegistry
{
    /** @var array<string, MetricDefinition> */
    private array $definitions = [];
    private bool  $loaded = false;

    public function __construct(private readonly string $basePath) {}

    public function get(string $key): MetricDefinition
    {
        $this->ensureLoaded();

        if (!isset($this->definitions[$key])) {
            throw new MetricNotFoundException("Metric [{$key}] not found.");
        }

        return $this->definitions[$key];
    }

    /** @return array<string, MetricDefinition> */
    public function all(): array
    {
        $this->ensureLoaded();
        return $this->definitions;
    }

    /** @return array<string, MetricDefinition> */
    public function approved(): array
    {
        return array_filter($this->all(), fn($m) => $m->isApproved());
    }

    public function flush(): void
    {
        $this->definitions = [];
        $this->loaded = false;
    }

    private function ensureLoaded(): void
    {
        if ($this->loaded) return;

        if (!is_dir($this->basePath)) {
            $this->loaded = true;
            return;
        }

        $finder = Finder::create()
            ->files()
            ->in($this->basePath)
            ->name('*.sql')
            ->sortByName();

        foreach ($finder as $file) {
            $key = $this->pathToKey(
                $file->getRelativePathname()
            );

            $this->definitions[$key] = MetricDefinition::fromFile(
                $file->getRealPath(),
                $key
            );
        }

        $this->loaded = true;
    }

    /**
     * Convert a relative file path to a dot-notation key.
     * e.g. revenue/by_month.sql → revenue.by_month
     */
    private function pathToKey(string $relativePath): string
    {
        return Str::of($relativePath)
            ->replace(['/', '\\'], '.')
            ->beforeLast('.sql')
            ->toString();
    }
}
