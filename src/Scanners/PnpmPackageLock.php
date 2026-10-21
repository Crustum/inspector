<?php
declare(strict_types=1);

namespace Crustum\Inspector\Scanners;

use Crustum\Inspector\PackageCollection;
use Exception;
use Symfony\Component\Yaml\Yaml;

/**
 * Scans pnpm-lock.yaml for pnpm dependencies.
 */
class PnpmPackageLock extends JsPackageScanner
{
    /**
     * Constructor.
     *
     * @param string $path Project base path
     * @param string $lockFile Lockfile name (pnpm shares its parser with Nub)
     */
    public function __construct(
        string $path,
        protected string $lockFile = 'pnpm-lock.yaml',
    ) {
        parent::__construct($path);
    }

    /**
     * @inheritDoc
     */
    public function scan(): PackageCollection
    {
        $packages = new PackageCollection();
        $lockFilePath = $this->path . $this->lockFile;

        $contents = $this->readContents($lockFilePath, $this->lockFile);

        if ($contents === null) {
            $this->failed = true;

            return $packages;
        }

        try {
            $parsed = Yaml::parse($contents);
        } catch (Exception) {
            $this->warn('Failed to parse ' . $this->lockFile . ': ' . $lockFilePath);
            $this->failed = true;

            return $packages;
        }

        if (!is_array($parsed)) {
            $this->warn('Malformed ' . $this->lockFile . ' (empty or not a mapping): ' . $lockFilePath);
            $this->failed = true;

            return $packages;
        }

        $allPackages = [];
        $packagesMap = is_array($parsed['packages'] ?? null) ? $parsed['packages'] : [];

        $lockfileVersion = $parsed['lockfileVersion'] ?? '';
        $slashStyle = is_scalar($lockfileVersion) && str_starts_with((string)$lockfileVersion, '5');

        foreach (array_keys($packagesMap) as $key) {
            $pair = $this->splitNameAndVersion((string)$key, $slashStyle);

            if ($pair === null) {
                continue;
            }

            [$name, $version] = $pair;

            if (isset($allPackages[$name])) {
                continue;
            }

            $allPackages[$name] = $version;
        }

        $importers = $parsed['importers'] ?? [];
        $root = $importers['.'] ?? $parsed;
        $rootDeps = is_array($root['dependencies'] ?? null) ? $root['dependencies'] : [];
        $rootDevDeps = is_array($root['devDependencies'] ?? null) ? $root['devDependencies'] : [];
        $rootOptionalDeps = is_array($root['optionalDependencies'] ?? null) ? $root['optionalDependencies'] : [];

        foreach ([...$rootDeps, ...$rootOptionalDeps] as $name => $data) {
            $version = $this->resolvedVersion($data);

            if ($version !== null) {
                $allPackages[(string)$name] = $version;
            }
        }

        $devPackages = [];

        foreach ($rootDevDeps as $name => $data) {
            $version = $this->resolvedVersion($data);

            if ($version !== null) {
                $devPackages[(string)$name] = $version;
                unset($allPackages[(string)$name]);
            }
        }

        $this->processDependencies($allPackages, $packages, false);
        $this->processDependencies($devPackages, $packages, true);

        return $packages;
    }

    /**
     * Resolve version from importer dependency data.
     *
     * @param mixed $data Importer dependency entry
     */
    private function resolvedVersion(mixed $data): ?string
    {
        $version = is_array($data) ? ($data['version'] ?? null) : $data;

        return is_scalar($version) ? $this->stripPeerSuffix((string)$version) : null;
    }

    /**
     * Split pnpm lock key into package name and version.
     *
     * @param string $key Lock key
     * @param bool $slashStyle Whether lockfile uses slash style versions
     * @return array{0: string, 1: string}|null
     */
    private function splitNameAndVersion(string $key, bool $slashStyle): ?array
    {
        $key = ltrim($this->stripPeerSuffix($key), '/');

        if ($slashStyle) {
            $position = strrpos($key, '/');

            if ($position === false || $position === 0) {
                return null;
            }

            $version = substr($key, $position + 1);
            $peer = strpos($version, '_');

            return [substr($key, 0, $position), $peer === false ? $version : substr($version, 0, $peer)];
        }

        $position = strrpos($key, '@');

        if ($position !== false && $position > 0) {
            return [substr($key, 0, $position), substr($key, $position + 1)];
        }

        $position = strrpos($key, '/');

        if ($position === false || $position === 0) {
            return null;
        }

        return [substr($key, 0, $position), substr($key, $position + 1)];
    }

    /**
     * Strip peer dependency suffix from lock key.
     *
     * @param string $value Lock key fragment
     * @return string
     */
    private function stripPeerSuffix(string $value): string
    {
        $paren = strpos($value, '(');

        return $paren === false ? $value : substr($value, 0, $paren);
    }
}
