<?php
declare(strict_types=1);

namespace Crustum\Inspector\Scanners;

use Crustum\Inspector\Package;
use Crustum\Inspector\PackageCollection;

/**
 * Scans direct dependencies from package.json only.
 */
class PackageJson extends JsPackageScanner
{
    /**
     * @inheritDoc
     */
    public function scan(): PackageCollection
    {
        $packages = new PackageCollection();

        foreach ($this->directDependencies() as $name => $meta) {
            $constraint = $meta['constraint'];

            $packages->push(new Package(
                name: $name,
                version: self::normalizeVersion($constraint),
                source: $this->source(),
                dev: $meta['isDev'],
                direct: true,
                constraint: $constraint,
                path: $this->computePath($name),
            ));
        }

        return $packages;
    }
}
