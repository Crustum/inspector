<?php

declare(strict_types=1);

use Crustum\Inspector\Enums\Agent;
use Crustum\Inspector\Enums\Approach;
use Crustum\Inspector\ProjectManager;
use Tests\Support\Cache\CorruptReadCacheEngine;
use Tests\Support\Cache\DownCacheEngine;
use Tests\Support\Cache\FailingWriteCacheEngine;

beforeEach(function (): void {
    inspectorUseArrayCache();
});

afterEach(function (): void {
    inspectorClearCache();
});

it('invalidates the cache when a project marker appears', function (): void {
    $base = tempBase();

    $manager = new ProjectManager();

    expect($manager->scan($base)->agents()->all())->toBe([]);

    mkdir($base . '.claude');

    expect($manager->scan($base)->agents()->uses(Agent::ClaudeCode))->toBeTrue();

    cleanup($base);
});

it('invalidates the cache when lockfile contents change', function (): void {
    $base = tempBase();
    file_put_contents($base . 'package.json', json_encode(['dependencies' => ['vue' => '^3.0']]));

    $manager = new ProjectManager();

    expect($manager->scan($base)->js()->uses('vue'))->toBeTrue();

    file_put_contents($base . 'package.json', json_encode(['dependencies' => ['react' => '^19.0']]));

    $rescanned = $manager->scan($base);
    expect($rescanned->js()->uses('react'))->toBeTrue();
    expect($rescanned->js()->uses('vue'))->toBeFalse();

    cleanup($base);
});

it('returns the cached project for an unchanged directory', function (): void {
    $base = tempBase();

    $manager = new ProjectManager();

    expect($manager->scan($base))->toBe($manager->scan($base));

    cleanup($base);
});

it('fresh bypasses the cache and forces a re-read', function (): void {
    $base = tempBase();

    $manager = new ProjectManager();
    $cached = $manager->scan($base);

    expect($manager->fresh($base))->not->toBe($cached);

    cleanup($base);
});

it('scans without a usable cache driver', function (): void {
    inspectorUseNullCache();

    $base = tempBase();
    file_put_contents($base . 'package.json', json_encode(['dependencies' => ['vue' => '^3.0']]));

    expect((new ProjectManager())->scan($base)->js()->uses('vue'))->toBeTrue();

    cleanup($base);
});

it('serves projects through a cache store that actually serializes', function (): void {
    $cacheDir = tempBase();
    $fileCachePath = $cacheDir . 'file-cache' . DIRECTORY_SEPARATOR;
    mkdir($fileCachePath);

    inspectorResetCacheConfig([
        'className' => 'File',
        'path' => $fileCachePath,
        'prefix' => '',
        'serialize' => true,
        'duration' => '+1 hour',
    ]);

    $base = testDirectory('approaches/accessible-whitelist-entities-app');

    $scanned = (new ProjectManager())->scan($base);
    $scanned->approaches();

    $restored = (new ProjectManager())->scan($base);

    expect($restored)->not->toBe($scanned)
        ->and($restored->approaches()->uses(Approach::EntityAccessibleWhitelist))->toBeTrue();

    inspectorUseArrayCache();
});

it('overwrites a corrupt cache entry instead of rescanning forever', function (): void {
    inspectorUseCacheEngine(CorruptReadCacheEngine::class);

    $base = tempBase();

    $manager = new ProjectManager();
    $scanned = $manager->scan($base);

    expect($manager->scan($base))->toBe($scanned);

    inspectorUseArrayCache();
    cleanup($base);
});

it('still scans when writing to the cache throws', function (): void {
    inspectorUseCacheEngine(FailingWriteCacheEngine::class);

    $base = tempBase();
    file_put_contents($base . 'package.json', json_encode(['dependencies' => ['vue' => '^3.0']]));

    expect((new ProjectManager())->scan($base)->js()->uses('vue'))->toBeTrue();

    inspectorUseArrayCache();
    cleanup($base);
});

it('still scans when the cache store throws', function (): void {
    inspectorUseCacheEngine(DownCacheEngine::class);

    $base = tempBase();
    file_put_contents($base . 'package.json', json_encode(['dependencies' => ['vue' => '^3.0']]));

    expect((new ProjectManager())->scan($base)->js()->uses('vue'))->toBeTrue();

    inspectorUseArrayCache();
    cleanup($base);
});

it('does not repoint the default instance when scanning another directory', function (): void {
    $base = tempBase();
    mkdir($base . '.claude');

    $manager = new ProjectManager();
    $default = $manager->instance();

    expect($manager->scan($base)->agents()->uses(Agent::ClaudeCode))->toBeTrue();

    expect($manager->instance())->toBe($default);
    expect($manager->agents()->uses(Agent::ClaudeCode))->toBeFalse();

    cleanup($base);
});

it('memoizes the default instance', function (): void {
    $manager = new ProjectManager();

    expect($manager->instance())->toBe($manager->instance());
});
