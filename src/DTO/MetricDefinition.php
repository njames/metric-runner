<?php

namespace YourOrg\MetricRunner\DTO;

use YourOrg\MetricRunner\Exceptions\InvalidMetricException;

final class MetricDefinition
{
    public function __construct(
        public readonly string $key,
        public readonly string $name,
        public readonly string $description,
        public readonly string $sql,
        public readonly array  $params,
        public readonly string $status,      // draft | review | approved
        public readonly array  $roles,       // [] = unrestricted
        public readonly ?int   $cacheTtl,    // null = use global default
        public readonly ?int   $timeout,     // null = use global default
        public readonly ?string $connection, // null = use global default
    ) {}

    public static function fromFile(string $path, string $key): self
    {
        $raw = file_get_contents($path);

        [$frontmatter, $sql] = self::parseFrontmatter($raw);

        $sql = trim($sql);

        if (empty($sql)) {
            throw new InvalidMetricException("Metric [{$key}] has no SQL body.");
        }

        return new self(
            key:        $key,
            name:       $frontmatter['name']        ?? $key,
            description:$frontmatter['description'] ?? '',
            sql:        $sql,
            params:     $frontmatter['params']       ?? [],
            status:     $frontmatter['status']       ?? 'draft',
            roles:      $frontmatter['roles']        ?? [],
            cacheTtl:   isset($frontmatter['cache_ttl'])  ? (int) $frontmatter['cache_ttl']  : null,
            timeout:    isset($frontmatter['timeout'])    ? (int) $frontmatter['timeout']     : null,
            connection: $frontmatter['connection']        ?? null,
        );
    }

    private static function parseFrontmatter(string $raw): array
    {
        // Frontmatter is a SQL block comment at the top: /* key: value */
        if (!str_starts_with(ltrim($raw), '/*')) {
            return [[], $raw];
        }

        $end = strpos($raw, '*/');

        if ($end === false) {
            return [[], $raw];
        }

        $block = substr($raw, strpos($raw, '/*') + 2, $end - 2);
        $sql   = substr($raw, $end + 2);

        $meta = [];
        foreach (explode("\n", $block) as $line) {
            $line = ltrim($line, "* \t");
            if (!str_contains($line, ':')) continue;

            [$rawKey, $rawValue] = explode(':', $line, 2);
            $k = trim($rawKey);
            $v = trim($rawValue);

            // Handle array values: roles: admin, analyst
            if (str_contains($v, ',')) {
                $meta[$k] = array_map('trim', explode(',', $v));
            } else {
                $meta[$k] = $v;
            }
        }

        return [$meta, $sql];
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }
}
