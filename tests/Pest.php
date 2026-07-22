<?php

declare(strict_types=1);

use Crustum\Inspector\Ecosystems\Ecosystem;
use Crustum\Inspector\Ecosystems\JsEcosystem;
use Crustum\Inspector\Enums\PackageSource;
use Crustum\Inspector\Package;
use Crustum\Inspector\PackageCollection;

expect()->extend('toBeOne', fn() => $this->toBe(1));

/**
 * @param array<int, string|array{name: string, version?: string, dev?: bool, direct?: bool}> $specs
 */
function phpEcosystem(array $specs): Ecosystem
{
    return new Ecosystem(packagesFromSpecs($specs, PackageSource::Composer));
}

/**
 * @param array<int, string|array{name: string, version?: string, dev?: bool, direct?: bool}> $specs
 */
function jsEcosystem(array $specs): JsEcosystem
{
    return new JsEcosystem(
        packagesFromSpecs($specs, PackageSource::Npm),
        null,
    );
}

/**
 * @param array<int, string|array{name: string, version?: string, dev?: bool, direct?: bool}> $specs
 */
function packagesFromSpecs(array $specs, PackageSource $source): PackageCollection
{
    $packages = new PackageCollection();

    foreach ($specs as $spec) {
        if (is_string($spec)) {
            $spec = ['name' => $spec];
        }

        $packages->push(new Package(
            name: $spec['name'],
            version: $spec['version'] ?? '1.0.0',
            source: $source,
            dev: $spec['dev'] ?? false,
            direct: $spec['direct'] ?? true,
        ));
    }

    return $packages;
}

/**
 * @return list<string>
 */
function trackedTempDirs(?string $add = null, bool $reset = false): array
{
    static $dirs = [];

    if ($reset) {
        $dirs = [];
    } elseif ($add !== null) {
        $dirs[] = $add;
    }

    return $dirs;
}

function tempBase(): string
{
    $base = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'inspector_test_' . uniqid() . DIRECTORY_SEPARATOR;
    mkdir($base);
    trackedTempDirs($base);

    return $base;
}

afterEach(function (): void {
    foreach (trackedTempDirs() as $dir) {
        cleanup($dir);
    }

    trackedTempDirs(reset: true);
});

/**
 * @param array<string, string> $files
 */
function fixtureCopy(array $files): string
{
    $base = tempBase();

    foreach ($files as $source => $destination) {
        copy(
            __DIR__ . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $source),
            $base . $destination,
        );
    }

    return $base;
}

function touchFile(string $path): void
{
    $dir = dirname($path);

    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    file_put_contents($path, '');
}

function cleanup(string $base): void
{
    if (!is_dir($base)) {
        return;
    }

    $normalized = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $base), DIRECTORY_SEPARATOR);
    $dirName = basename($normalized);

    if (!str_starts_with($dirName, 'inspector_test_')) {
        return;
    }

    $tempRoot = realpath(sys_get_temp_dir());
    $realBase = realpath($normalized);

    if ($tempRoot === false || $realBase === false || !str_starts_with($realBase, $tempRoot . DIRECTORY_SEPARATOR)) {
        return;
    }

    $iter = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($realBase, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );

    foreach ($iter as $f) {
        $f->isDir() ? rmdir($f->getPathname()) : unlink($f->getPathname());
    }

    rmdir($realBase);
}
