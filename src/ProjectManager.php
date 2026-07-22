<?php
declare(strict_types=1);

namespace Crustum\Inspector;

use Cake\Cache\Cache;
use Closure;
use Crustum\Inspector\Detectors\AgentsDetector;
use Crustum\Inspector\Detectors\BrowserTestFrameworkDetector;
use Crustum\Inspector\Detectors\EditorsDetector;
use Crustum\Inspector\Detectors\MarkerDetector;
use Crustum\Inspector\Ecosystems\Ecosystem;
use Crustum\Inspector\Ecosystems\JsEcosystem;
use Crustum\Inspector\Enums\JsPackageManager;
use Crustum\Inspector\Support\ApproachSet;
use Crustum\Inspector\Support\EnumSet;
use Throwable;

/**
 * Cached entry point for project scans.
 */
class ProjectManager
{
    protected const CACHE_CONFIG = 'inspector';

    /**
     * Default cached scan instance.
     *
     * @var \Crustum\Inspector\ProjectScan|null
     */
    protected ?ProjectScan $cached = null;

    /**
     * Scan a project, using cache when available.
     *
     * @param string|null $basePath Project base path
     * @return \Crustum\Inspector\ProjectScan
     */
    public function scan(?string $basePath = null): ProjectScan
    {
        $resolvedBase = ProjectScan::normalizeBasePath($basePath);

        $project = $this->rememberScan(
            $this->cacheKey($resolvedBase),
            fn(): ProjectScan => ProjectScan::scan($resolvedBase),
        );

        return $basePath === null ? ($this->cached = $project) : $project;
    }

    /**
     * Scan a project bypassing cache reads.
     *
     * @param string|null $basePath Project base path
     * @return \Crustum\Inspector\ProjectScan
     */
    public function fresh(?string $basePath = null): ProjectScan
    {
        $project = ProjectScan::scan(ProjectScan::normalizeBasePath($basePath));

        return $basePath === null ? ($this->cached = $project) : $project;
    }

    /**
     * Default scan instance for the current working directory.
     *
     * @return \Crustum\Inspector\ProjectScan
     */
    public function instance(): ProjectScan
    {
        return $this->cached ??= $this->scan();
    }

    /**
     * PHP package ecosystem from the default scan.
     *
     * @return \Crustum\Inspector\Ecosystems\Ecosystem
     */
    public function php(): Ecosystem
    {
        return $this->instance()->php();
    }

    /**
     * JS package ecosystem from the default scan.
     *
     * @return \Crustum\Inspector\Ecosystems\JsEcosystem
     */
    public function js(): JsEcosystem
    {
        return $this->instance()->js();
    }

    /**
     * Detected stacks from the default scan.
     *
     * @return \Crustum\Inspector\Support\EnumSet<\Crustum\Inspector\Enums\Stack>
     */
    public function stacks(): EnumSet
    {
        return $this->instance()->stacks();
    }

    /**
     * Detected browser frameworks from the default scan.
     *
     * @return \Crustum\Inspector\Support\EnumSet<\Crustum\Inspector\Enums\BrowserTestFramework>
     */
    public function browserTestFrameworks(): EnumSet
    {
        return $this->instance()->browserTestFrameworks();
    }

    /**
     * Detected frontends from the default scan.
     *
     * @return \Crustum\Inspector\Support\EnumSet<\Crustum\Inspector\Enums\Frontend>
     */
    public function frontends(): EnumSet
    {
        return $this->instance()->frontends();
    }

    /**
     * Detected agents from the default scan.
     *
     * @return \Crustum\Inspector\Support\EnumSet<\Crustum\Inspector\Enums\Agent>
     */
    public function agents(): EnumSet
    {
        return $this->instance()->agents();
    }

    /**
     * Detected editors from the default scan.
     *
     * @return \Crustum\Inspector\Support\EnumSet<\Crustum\Inspector\Enums\Editor>
     */
    public function editors(): EnumSet
    {
        return $this->instance()->editors();
    }

    /**
     * Detected approaches from the default scan.
     *
     * @return \Crustum\Inspector\Support\ApproachSet
     */
    public function approaches(): ApproachSet
    {
        return $this->instance()->approaches();
    }

    /**
     * Default scan as array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->instance()->toArray();
    }

    /**
     * Default scan as JSON.
     *
     * @return string
     */
    public function json(): string
    {
        return $this->instance()->json();
    }

    /**
     * Remember a scan in cache.
     *
     * @param string $key Cache key
     * @param \Closure(): \Crustum\Inspector\ProjectScan $scan Scan factory
     * @return \Crustum\Inspector\ProjectScan
     */
    private function rememberScan(string $key, Closure $scan): ProjectScan
    {
        try {
            $cached = Cache::read($key, self::CACHE_CONFIG);

            if ($cached instanceof ProjectScan) {
                return $cached;
            }
        } catch (Throwable) {
        }

        $project = $scan();

        try {
            Cache::write($key, $project, self::CACHE_CONFIG);
        } catch (Throwable) {
        }

        return $project;
    }

    /**
     * Build cache key for a base path.
     *
     * @param string $basePath Normalized base path
     * @return string
     */
    private function cacheKey(string $basePath): string
    {
        return 'inspector:project:v3:' . md5(
            $basePath . '|' . $this->lockfileHash($basePath) . '|' . $this->markerHash($basePath),
        );
    }

    /**
     * Lockfiles included in cache invalidation.
     *
     * @return list<string>
     */
    private function lockfiles(): array
    {
        $jsLockfiles = array_merge(...array_map(
            fn(JsPackageManager $manager): array => $manager->lockFiles(),
            JsPackageManager::cases(),
        ));

        return ['composer.lock', 'composer.json', ...$jsLockfiles, 'package.json'];
    }

    /**
     * Hash committed lockfile contents.
     *
     * @param string $basePath Normalized base path
     * @return string
     */
    private function lockfileHash(string $basePath): string
    {
        $hash = hash_init('md5');

        foreach ($this->lockfiles() as $file) {
            $path = $basePath . $file;
            hash_update($hash, $file . ':' . $this->fileChecksum($path) . '|');
        }

        return hash_final($hash);
    }

    /**
     * MD5 checksum for a lockfile when readable.
     *
     * @param string $path Absolute file path
     * @return string
     */
    private function fileChecksum(string $path): string
    {
        if (!is_file($path) || !is_readable($path)) {
            return '0';
        }

        $checksum = md5_file($path);

        return $checksum !== false ? $checksum : '0';
    }

    /**
     * Hash marker presence for cache invalidation.
     *
     * @param string $basePath Normalized base path
     * @return string
     */
    private function markerHash(string $basePath): string
    {
        $markers = [
            ...AgentsDetector::markerPaths(),
            ...EditorsDetector::markerPaths(),
            ...BrowserTestFrameworkDetector::markerPaths(),
        ];

        $markers = array_values(array_unique($markers));
        sort($markers);

        $hash = hash_init('md5');

        foreach ($markers as $marker) {
            hash_update($hash, $marker . ':' . (MarkerDetector::markerMatches($basePath, $marker) ? '1' : '0') . '|');
        }

        return hash_final($hash);
    }
}
