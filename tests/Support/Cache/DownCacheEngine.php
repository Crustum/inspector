<?php
declare(strict_types=1);

namespace Tests\Support\Cache;

use Cake\Cache\CacheEngine;
use DateInterval;
use RuntimeException;

/**
 * Cache engine that simulates a completely unavailable cache store.
 */
class DownCacheEngine extends CacheEngine
{
    /**
     * @inheritDoc
     */
    #[\Override]
    public function init(array $config = []): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        throw new RuntimeException('cache is down');
    }

    /**
     * @inheritDoc
     */
    public function get(string $key, mixed $default = null): mixed
    {
        throw new RuntimeException('cache is down');
    }

    /**
     * @inheritDoc
     */
    public function increment(string $key, int $offset = 1): int|false
    {
        throw new RuntimeException('cache is down');
    }

    /**
     * @inheritDoc
     */
    public function decrement(string $key, int $offset = 1): int|false
    {
        throw new RuntimeException('cache is down');
    }

    /**
     * @inheritDoc
     */
    public function delete(string $key): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function clear(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function clearGroup(string $group): bool
    {
        return true;
    }
}
