<?php
declare(strict_types=1);

namespace Crustum\Inspector\Scanners;

use Crustum\Inspector\PackageCollection;

/**
 * Scans yarn.lock for yarn dependencies.
 */
class YarnPackageLock extends JsPackageScanner
{
    private const YARN_V4_HEADER = '/^"(@?[^@"]+(?:\/[^@"]+)?)@npm:[^"]*":\s*$/';

    private const YARN_V1_VERSION = '/^version\s+"([^"]+)"$/';

    private const YARN_V4_VERSION = '/^version:\s+(.+)$/';

    /**
     * @inheritDoc
     */
    public function scan(): PackageCollection
    {
        $packages = new PackageCollection();
        $lockFilePath = $this->path . 'yarn.lock';

        $contents = $this->readContents($lockFilePath, 'yarn.lock');

        if ($contents === null) {
            $this->failed = true;

            return $packages;
        }

        $dependencies = [];
        $lines = explode("\n", $contents);
        $currentPackage = null;

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            if (str_starts_with($line, '#')) {
                continue;
            }

            $packageName = $this->parsePackageHeader($line);

            if ($packageName !== null) {
                $currentPackage = $packageName;

                continue;
            }

            $version = $this->parseVersion($line);

            if ($currentPackage !== null && $version !== null) {
                $dependencies[$currentPackage] = $version;
                $currentPackage = null;
            }
        }

        if ($dependencies === []) {
            $this->failed = true;

            return $packages;
        }

        $this->processDependencies($dependencies, $packages, false);

        return $packages;
    }

    /**
     * Parse a yarn lock package header line.
     *
     * @param string $line Lockfile line
     */
    private function parsePackageHeader(string $line): ?string
    {
        if (preg_match(self::YARN_V4_HEADER, $line, $matches)) {
            return $matches[1];
        }

        if (!str_ends_with($line, ':')) {
            return null;
        }

        $selector = trim((string)strtok(substr($line, 0, -1), ','), " \t\"");
        $position = strpos($selector, '@', 1);

        if ($position === false || $position === 0) {
            return null;
        }

        if (str_starts_with(substr($selector, $position + 1), 'workspace:')) {
            return null;
        }

        return substr($selector, 0, $position);
    }

    /**
     * Parse a yarn lock version line.
     *
     * @param string $line Lockfile line
     */
    private function parseVersion(string $line): ?string
    {
        if (preg_match(self::YARN_V1_VERSION, $line, $matches)) {
            return $matches[1];
        }

        if (preg_match(self::YARN_V4_VERSION, $line, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }
}
