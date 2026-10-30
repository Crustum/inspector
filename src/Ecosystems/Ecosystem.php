<?php
declare(strict_types=1);

namespace Crustum\Inspector\Ecosystems;

use Composer\Semver\Semver;
use Composer\Semver\VersionParser;
use Crustum\Inspector\Package;
use Crustum\Inspector\PackageCollection;
use Crustum\Inspector\Scanners\CakePluginResolver;
use InvalidArgumentException;
use UnexpectedValueException;

/**
 * Package ecosystem with semver-aware membership checks.
 */
class Ecosystem
{
    /**
     * Packages indexed by name.
     *
     * @var array<string, \Crustum\Inspector\Package>
     */
    protected array $byName = [];

    /**
     * Constructor.
     *
     * @param \Crustum\Inspector\PackageCollection $packages Scanned packages
     */
    public function __construct(
        protected PackageCollection $packages,
    ) {
        foreach ($packages as $package) {
            $this->byName[$package->name()] ??= $package;
        }
    }

    /**
     * Whether any package is present, optionally matching a constraint.
     *
     * @param array<int|string, string>|string $packages Package name or map
     * @param string|null $constraint Semver constraint for a single package
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function uses(string|array $packages, ?string $constraint = null): bool
    {
        if (is_string($packages)) {
            return $this->satisfies($packages, $constraint);
        }

        if ($constraint !== null) {
            throw new InvalidArgumentException('The second argument is only valid when the first is a single package name.');
        }

        foreach ($this->normalize($packages) as [$name, $packageConstraint]) {
            if ($this->satisfies($name, $packageConstraint)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether all packages are present.
     *
     * @param array<int|string, string> $packages Package names or map
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function usesAll(array $packages): bool
    {
        foreach ($this->normalize($packages) as [$name, $packageConstraint]) {
            if (!$this->satisfies($name, $packageConstraint)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Whether any package is a direct dependency.
     *
     * @param list<string>|string $packages Package names
     * @return bool
     */
    public function usesDirect(string|array $packages): bool
    {
        return array_any((array)$packages, fn(string $name): bool => $this->package($name)?->isDirect() ?? false);
    }

    /**
     * Lookup a package by name.
     *
     * @param string $name Package name
     */
    public function package(string $name): ?Package
    {
        return $this->byName[$name] ?? null;
    }

    /**
     * All scanned packages.
     *
     * @return \Crustum\Inspector\PackageCollection
     */
    public function packages(): PackageCollection
    {
        return $this->packages;
    }

    /**
     * Packages matching a composer type (e.g. `cakephp-plugin`).
     *
     * @param string $type Package type to match
     * @return \Crustum\Inspector\PackageCollection
     */
    public function byType(string $type): PackageCollection
    {
        return $this->packages->byType($type);
    }

    /**
     * Installed CakePHP plugins (`type: cakephp-plugin`).
     *
     * Includes unloaded plugins; use `Plugin::loaded()` separately
     * when the loaded state matters.
     *
     * @return \Crustum\Inspector\PackageCollection
     */
    public function cakePlugins(): PackageCollection
    {
        return $this->packages->byType('cakephp-plugin');
    }

    /**
     * Whether any package of the given composer type is present.
     *
     * @param string $type Package type to check
     * @return bool
     */
    public function usesType(string $type): bool
    {
        return $this->byType($type)->count() > 0;
    }

    /**
     * Resolved CakePHP plugins for installed `cakephp-plugin` packages.
     *
     * Each entry carries the composer name, resolved plugin class, Cake
     * name and install path — everything needed to inspect a plugin
     * without loading it. Packages without a resolvable plugin class
     * are skipped.
     *
     * @param list<string> $extraPaths Additional plugin directories to scan
     * @return list<\Crustum\Inspector\CakePlugin>
     */
    public function cakePluginDetails(array $extraPaths = []): array
    {
        return (new CakePluginResolver())->resolve(
            $this->packages->byType('cakephp-plugin')->all(),
            $extraPaths,
        );
    }

    /**
     * Check semver satisfaction for a package.
     *
     * @param string $name Package name
     * @param string|null $constraint Semver constraint
     * @return bool
     */
    private function satisfies(string $name, ?string $constraint): bool
    {
        if ($constraint !== null) {
            try {
                (new VersionParser())->parseConstraints($constraint);
            } catch (UnexpectedValueException $exception) {
                throw new InvalidArgumentException("Invalid semver constraint: {$constraint}", $exception->getCode(), $exception);
            }
        }

        $package = $this->package($name);

        if (!$package instanceof Package) {
            return false;
        }

        if ($constraint === null) {
            return true;
        }

        $version = $package->version();

        if ($version === '') {
            return false;
        }

        try {
            return Semver::satisfies($version, $constraint);
        } catch (UnexpectedValueException) {
            return false;
        }
    }

    /**
     * Normalize package list input.
     *
     * @param array<int|string, string> $packages Package names or map
     * @return list<array{0: string, 1: string|null}>
     * @throws \InvalidArgumentException
     */
    private function normalize(array $packages): array
    {
        if ($packages === []) {
            return [];
        }

        if (array_is_list($packages)) {
            return array_map(fn(string $name): array => [$name, null], $packages);
        }

        $pairs = [];

        foreach ($packages as $name => $constraint) {
            if (!is_string($name)) {
                throw new InvalidArgumentException('Array must be either all-indexed (list of names) or all-assoc (name => constraint), not mixed.');
            }

            $pairs[] = [$name, $constraint];
        }

        return $pairs;
    }
}
