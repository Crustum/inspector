<?php
declare(strict_types=1);

use Cake\Cache\Cache;
use Cake\Cache\Engine\ArrayEngine;
use Cake\Cache\Engine\NullEngine;
use Cake\Collection\CollectionInterface;
use Tests\Support\ExtendedCollection;

/**
 * Wrap scan results for callback-based first()/contains() in ported tests.
 *
 * @param \Cake\Collection\CollectionInterface|iterable<mixed> $items Source items
 * @return \Tests\Support\ExtendedCollection
 */
function inspectorCollection(CollectionInterface|iterable $items): ExtendedCollection
{
    return ExtendedCollection::wrap($items);
}

/**
 * Resolve a path under tests/fixtures.
 *
 * @param string $path Relative fixture path
 * @return string
 */
function testDirectory(string $path): string
{
    $relative = ltrim(str_replace('/', DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);

    if (!str_starts_with($relative, 'fixtures' . DIRECTORY_SEPARATOR)) {
        $relative = 'fixtures' . DIRECTORY_SEPARATOR . $relative;
    }

    return dirname(__DIR__) . DIRECTORY_SEPARATOR . $relative;
}

/**
 * Configure the inspector project cache for tests.
 *
 * @param bool $serialize Whether cached scans are serialized
 * @return void
 */
function inspectorUseArrayCache(bool $serialize = true): void
{
    inspectorResetCacheConfig([
        'className' => ArrayEngine::class,
        'prefix' => 'inspector_test_',
        'serialize' => $serialize,
        'duration' => '+1 hour',
    ]);
}

/**
 * Configure a no-op cache store for tests.
 *
 * @return void
 */
function inspectorUseNullCache(): void
{
    inspectorResetCacheConfig([
        'className' => NullEngine::class,
    ]);
}

/**
 * Configure a custom cache engine for tests.
 *
 * @param class-string $engineClass Cache engine class name
 * @param array<string, mixed> $config Additional cache config
 * @return void
 */
function inspectorUseCacheEngine(string $engineClass, array $config = []): void
{
    inspectorResetCacheConfig([
        'className' => $engineClass,
        'prefix' => 'inspector_test_',
        'serialize' => true,
        'duration' => '+1 hour',
        ...$config,
    ]);
}

/**
 * @param array<string, mixed> $config Cache config for the inspector store
 * @return void
 */
function inspectorResetCacheConfig(array $config): void
{
    if (Cache::getConfig('inspector') !== null) {
        Cache::clear('inspector');
        Cache::drop('inspector');
    }

    Cache::setConfig('inspector', $config);
}

/**
 * Clear the inspector cache between tests.
 *
 * @return void
 */
function inspectorClearCache(): void
{
    if (Cache::getConfig('inspector') !== null) {
        Cache::clear('inspector');
    }
}
