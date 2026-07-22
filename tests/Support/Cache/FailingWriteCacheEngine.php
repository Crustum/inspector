<?php
declare(strict_types=1);

namespace Tests\Support\Cache;

use Cake\Cache\Engine\ArrayEngine;
use DateInterval;
use RuntimeException;

/**
 * Array cache engine that always fails writes.
 */
class FailingWriteCacheEngine extends ArrayEngine
{
    /**
     * @inheritDoc
     */
    #[\Override]
    public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        throw new RuntimeException('cache write failed');
    }
}
