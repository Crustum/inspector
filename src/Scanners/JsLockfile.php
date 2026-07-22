<?php
declare(strict_types=1);

namespace Crustum\Inspector\Scanners;

use Crustum\Inspector\Enums\JsPackageManager;
use Crustum\Inspector\PackageCollection;

/**
 * Selects the committed JS lockfile scanner for a project.
 */
class JsLockfile
{
    /**
     * Constructor.
     *
     * @param string $path Project base path
     */
    public function __construct(
        protected string $path,
    ) {
        $this->path = PackageScanner::finishPath($path);
    }

    /**
     * Scan JS packages from the committed lockfile or package.json fallback.
     *
     * @return \Crustum\Inspector\PackageCollection
     */
    public function scan(): PackageCollection
    {
        $manager = $this->committedManager();

        if (!$manager instanceof JsPackageManager || !file_exists($this->path . $manager->lockFile())) {
            return (new PackageJson($this->path))->scan();
        }

        $scanner = $this->scannerFor($manager);
        $packages = $scanner->scan();

        return $scanner->failed() ? (new PackageJson($this->path))->scan() : $packages;
    }

    /**
     * Committed JS package manager from lockfile presence.
     */
    public function committedManager(): ?JsPackageManager
    {
        foreach (JsPackageManager::cases() as $case) {
            foreach ($case->lockFiles() as $lockFile) {
                if (file_exists($this->path . $lockFile)) {
                    return $case;
                }
            }
        }

        return null;
    }

    /**
     * Lockfile scanner for a package manager.
     *
     * @param \Crustum\Inspector\Enums\JsPackageManager $manager Package manager
     * @return \Crustum\Inspector\Scanners\JsPackageScanner
     */
    private function scannerFor(JsPackageManager $manager): JsPackageScanner
    {
        return match ($manager) {
            JsPackageManager::Npm => new NpmPackageLock($this->path),
            JsPackageManager::Pnpm => new PnpmPackageLock($this->path),
            JsPackageManager::Yarn => new YarnPackageLock($this->path),
            JsPackageManager::Bun => new BunPackageLock($this->path),
        };
    }
}
