<?php
declare(strict_types=1);

namespace Tests\Support\Cache;

use Cake\Cache\Engine\ArrayEngine;
use DateInterval;
use RuntimeException;

/**
 * Array cache engine that fails the first read to simulate corrupt cache data.
 */
class CorruptReadCacheEngine extends ArrayEngine
{
    public bool $corrupt = true;

    /**
     * @inheritDoc
     */
    #[\Override]
    public function get(string $key, mixed $default = null): mixed
    {
        if ($this->corrupt) {
            $this->corrupt = false;

            throw new RuntimeException('unserialize failed');
        }

        return parent::get($key, $default);
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        return parent::set($key, $value, $ttl);
    }
}
