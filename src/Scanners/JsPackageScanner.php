<?php
declare(strict_types=1);

namespace Crustum\Inspector\Scanners;

use Crustum\Inspector\Enums\PackageSource;

/**
 * Base scanner for JavaScript package lockfiles.
 */
abstract class JsPackageScanner extends PackageScanner
{
    /**
     * Whether scanning failed.
     *
     * @var bool
     */
    protected bool $failed = false;

    /**
     * Whether the scanner failed to parse its lockfile.
     *
     * @return bool
     */
    public function failed(): bool
    {
        return $this->failed;
    }

    /**
     * @inheritDoc
     */
    protected function source(): PackageSource
    {
        return PackageSource::Npm;
    }

    /**
     * @inheritDoc
     */
    protected function manifestFile(): string
    {
        return 'package.json';
    }

    /**
     * @inheritDoc
     */
    protected function manifestSections(): array
    {
        return [
            'devDependencies' => true,
            'peerDependencies' => false,
            'optionalDependencies' => false,
            'dependencies' => false,
        ];
    }

    /**
     * @inheritDoc
     */
    protected function computePath(string $packageName): string
    {
        return $this->resolvedBase() . DIRECTORY_SEPARATOR . 'node_modules' . DIRECTORY_SEPARATOR
            . str_replace('/', DIRECTORY_SEPARATOR, $packageName);
    }
}
