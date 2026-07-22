<?php
declare(strict_types=1);

namespace Crustum\Inspector\Scanners;

use Cake\Log\Log;
use Crustum\Inspector\Enums\PackageSource;
use Crustum\Inspector\Package;
use Crustum\Inspector\PackageCollection;
use Crustum\Inspector\Scanners\Trait\ParsesManifestsTrait;

/**
 * Base scanner for composer and JS lockfile manifests.
 */
abstract class PackageScanner
{
    use ParsesManifestsTrait;

    /**
     * Cached direct dependency map.
     *
     * @var array<string, array{constraint: string, isDev: bool}>|null
     */
    protected ?array $directPackages = null;

    /**
     * Resolved absolute base path.
     *
     * @var string|null
     */
    protected ?string $resolvedBase = null;

    /**
     * Cached manifest contents.
     *
     * @var array<string, mixed>|null
     */
    protected ?array $manifest = null;

    /**
     * Whether manifest was loaded.
     *
     * @var bool
     */
    protected bool $manifestLoaded = false;

    /**
     * Constructor.
     *
     * @param string $path Project base path
     */
    public function __construct(
        protected string $path,
    ) {
        $this->path = self::finishPath($path);
    }

    /**
     * Scan lockfile and manifest into packages.
     *
     * @return \Crustum\Inspector\PackageCollection
     */
    abstract public function scan(): PackageCollection;

    /**
     * Package source for this scanner.
     *
     * @return \Crustum\Inspector\Enums\PackageSource
     */
    abstract protected function source(): PackageSource;

    /**
     * Manifest filename relative to base path.
     *
     * @return string
     */
    abstract protected function manifestFile(): string;

    /**
     * Manifest sections used for direct dependency detection.
     *
     * @return array<string, bool>
     */
    abstract protected function manifestSections(): array;

    /**
     * Compute install path for a package.
     *
     * @param string $packageName Package name
     * @return string
     */
    abstract protected function computePath(string $packageName): string;

    /**
     * Add dependencies to the collection.
     *
     * @param array<string, string> $dependencies Package versions
     * @param \Crustum\Inspector\PackageCollection $packages Target collection
     * @param bool $isDev Default dev flag for transitive packages
     * @param bool $authoritative Use manifest dev flags when direct
     * @return void
     */
    protected function processDependencies(
        array $dependencies,
        PackageCollection $packages,
        bool $isDev,
        bool $authoritative = false,
    ): void {
        $direct = $this->directDependencies();

        foreach ($dependencies as $packageName => $version) {
            $packageName = (string)$packageName;

            if ($packageName === '') {
                continue;
            }

            $isDirect = array_key_exists($packageName, $direct);

            $packages->push(new Package(
                name: $packageName,
                version: self::normalizeVersion($version),
                source: $this->source(),
                dev: $isDirect && !$authoritative ? $direct[$packageName]['isDev'] : $isDev,
                direct: $isDirect,
                constraint: $isDirect ? $direct[$packageName]['constraint'] : '',
                path: $this->computePath($packageName),
            ));
        }
    }

    /**
     * Direct dependencies from manifest.
     *
     * @return array<string, array{constraint: string, isDev: bool}>
     */
    protected function directDependencies(): array
    {
        if ($this->directPackages !== null) {
            return $this->directPackages;
        }

        $manifest = $this->manifest();

        if ($manifest === null) {
            return $this->directPackages = [];
        }

        return $this->directPackages = self::collectManifestDeps($manifest, $this->manifestSections());
    }

    /**
     * Load and cache manifest JSON.
     *
     * @return array<string, mixed>|null
     */
    protected function manifest(): ?array
    {
        if (!$this->manifestLoaded) {
            $this->manifest = self::readJsonFile($this->path . $this->manifestFile());
            $this->manifestLoaded = true;
        }

        return $this->manifest;
    }

    /**
     * Resolved absolute base path.
     *
     * @return string
     */
    protected function resolvedBase(): string
    {
        return $this->resolvedBase ??= (realpath($this->path) ?: $this->path);
    }

    /**
     * Read JSON or warn on decode failure.
     *
     * @param string $file Relative filename
     * @return array<string, mixed>|null
     */
    protected function readJsonOrWarn(string $file): ?array
    {
        $path = $this->path . $file;
        $json = self::readJsonFile($path);

        if ($json === null && file_exists($path)) {
            $this->warn("Failed to decode {$file}: {$path}");
        }

        return $json;
    }

    /**
     * Read file contents or warn on failure.
     *
     * @param string $path Absolute path
     * @param string $label Label for warning message
     */
    protected function readContents(string $path, string $label): ?string
    {
        if (!file_exists($path) || !is_readable($path)) {
            $this->warn("Failed to read {$label}: {$path}");

            return null;
        }

        $contents = file_get_contents($path);

        return $contents === false ? null : $contents;
    }

    /**
     * Log a scanner warning.
     *
     * @param string $message Warning message
     * @return void
     */
    protected function warn(string $message): void
    {
        Log::warning($message, ['scope' => ['inspector']]);
    }

    /**
     * Ensure path ends with directory separator.
     *
     * @param string $path Path to normalize
     * @return string
     */
    public static function finishPath(string $path): string
    {
        return rtrim($path, DIRECTORY_SEPARATOR . '/') . DIRECTORY_SEPARATOR;
    }
}
