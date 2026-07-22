<?php
declare(strict_types=1);

namespace Crustum\Inspector\Ecosystems;

use Crustum\Inspector\Enums\JsPackageManager;
use Crustum\Inspector\PackageCollection;

/**
 * JavaScript package ecosystem with committed lockfile manager detection.
 */
class JsEcosystem extends Ecosystem
{
    /**
     * Constructor.
     *
     * @param \Crustum\Inspector\PackageCollection $packages Scanned packages
     * @param \Crustum\Inspector\Enums\JsPackageManager|null $packageManager Committed manager
     */
    public function __construct(
        PackageCollection $packages,
        protected ?JsPackageManager $packageManager,
    ) {
        parent::__construct($packages);
    }

    /**
     * Committed JS package manager when a lockfile is present.
     */
    public function packageManager(): ?JsPackageManager
    {
        return $this->packageManager;
    }

    /**
     * Whether the project uses the given JS package manager.
     *
     * @param \Crustum\Inspector\Enums\JsPackageManager|string $packageManager Manager enum or value
     */
    public function usesPackageManager(JsPackageManager|string $packageManager): bool
    {
        if (is_string($packageManager)) {
            $packageManager = JsPackageManager::tryFrom($packageManager);
        }

        return $packageManager !== null && $this->packageManager === $packageManager;
    }
}
