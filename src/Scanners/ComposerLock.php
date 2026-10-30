<?php
declare(strict_types=1);

namespace Crustum\Inspector\Scanners;

use Composer\Semver\VersionParser;
use Crustum\Inspector\Enums\PackageSource;
use Crustum\Inspector\PackageCollection;
use UnexpectedValueException;

/**
 * Scans composer.lock for installed PHP packages.
 */
class ComposerLock extends PackageScanner
{
    /**
     * @inheritDoc
     */
    public function scan(): PackageCollection
    {
        $packages = new PackageCollection();

        $json = $this->readJsonOrWarn('composer.lock');

        if ($json === null) {
            return $packages;
        }

        if (!is_array($json['packages'] ?? null)) {
            $this->warn('Malformed composer.lock (missing "packages" key): ' . $this->path . 'composer.lock');
        }

        $this->processDependencies($this->entries($json['packages'] ?? null), $packages, false, authoritative: true);
        $this->processDependencies($this->entries($json['packages-dev'] ?? null), $packages, true, authoritative: true);

        return $packages;
    }

    /**
     * Minimum PHP major.minor version required by the root composer.json.
     *
     * Returns null when the requirement is missing or has no usable lower bound.
     *
     * @return string|null
     */
    public function minimumPhpVersion(): ?string
    {
        $require = $this->manifest()['require'] ?? null;

        if (!is_array($require) || !is_string($require['php'] ?? null)) {
            return null;
        }

        try {
            $lowerBound = (new VersionParser())
                ->parseConstraints($require['php'])
                ->getLowerBound();
        } catch (UnexpectedValueException) {
            return null;
        }

        if ($lowerBound->isZero()) {
            return null;
        }

        if (preg_match('/^(\d+)\.(\d+)/', $lowerBound->getVersion(), $matches) !== 1) {
            return null;
        }

        return $matches[1] . '.' . $matches[2];
    }

    /**
     * @inheritDoc
     */
    protected function source(): PackageSource
    {
        return PackageSource::Composer;
    }

    /**
     * @inheritDoc
     */
    protected function manifestFile(): string
    {
        return 'composer.json';
    }

    /**
     * @inheritDoc
     */
    protected function manifestSections(): array
    {
        return [
            'require-dev' => true,
            'require' => false,
        ];
    }

    /**
     * @inheritDoc
     */
    protected function computePath(string $packageName): string
    {
        $vendorPath = str_replace('/', DIRECTORY_SEPARATOR, $this->vendorDir());
        $packageSegment = str_replace('/', DIRECTORY_SEPARATOR, $packageName);

        if ($this->isAbsolutePath($vendorPath)) {
            return $vendorPath . DIRECTORY_SEPARATOR . $packageSegment;
        }

        return $this->resolvedBase() . DIRECTORY_SEPARATOR . $vendorPath . DIRECTORY_SEPARATOR . $packageSegment;
    }

    /**
     * Extract package versions and types from lockfile entries.
     *
     * @param mixed $rawPackages Lockfile package list
     * @return array<string, array{version: string, type: string}>
     */
    private function entries(mixed $rawPackages): array
    {
        if (!is_array($rawPackages)) {
            return [];
        }

        $entries = [];

        foreach ($rawPackages as $raw) {
            if (!is_array($raw)) {
                continue;
            }

            $name = $raw['name'] ?? null;
            if (!is_string($name)) {
                continue;
            }

            if ($name === '') {
                continue;
            }

            $version = $raw['version'] ?? null;
            $type = $raw['type'] ?? null;

            $entries[$name] = [
                'version' => is_string($version) ? $version : '',
                'type' => is_string($type) ? $type : '',
            ];
        }

        return $entries;
    }

    /**
     * @inheritDoc
     */
    protected function resolveType(string $packageName, string $type, string $path): string
    {
        if ($type !== '') {
            return $type;
        }

        $composerJson = $path . DIRECTORY_SEPARATOR . 'composer.json';
        $manifest = self::readJsonFile($composerJson);

        if (!is_array($manifest)) {
            return '';
        }

        $manifestType = $manifest['type'] ?? null;

        return is_string($manifestType) ? $manifestType : '';
    }

    /**
     * Vendor directory from composer.json config.
     *
     * @return string
     */
    private function vendorDir(): string
    {
        $config = $this->manifest()['config'] ?? null;

        if (is_array($config) && isset($config['vendor-dir']) && is_string($config['vendor-dir'])) {
            return $config['vendor-dir'];
        }

        return 'vendor';
    }

    /**
     * Whether a path is absolute on the current platform.
     *
     * @param string $path Path to test
     * @return bool
     */
    private function isAbsolutePath(string $path): bool
    {
        if (DIRECTORY_SEPARATOR === '/') {
            return str_starts_with($path, '/');
        }

        return (bool)preg_match('/^[A-Za-z]:[\\\\\\/]/', $path);
    }
}
