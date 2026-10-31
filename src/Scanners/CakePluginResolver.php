<?php
declare(strict_types=1);

namespace Crustum\Inspector\Scanners;

use Cake\Utility\Inflector;
use Crustum\Inspector\CakePlugin;
use Crustum\Inspector\Package;
use ReflectionClass;
use Throwable;

/**
 * Resolves CakePHP plugin classes for installed composer packages.
 *
 * Combines the composer-name convention (`vendor/name` →
 * `Vendor\Name\NamePlugin`) with the package PSR-4 autoload map
 * (`src/*Plugin.php` glob). Plugin files are required on demand so path
 * checkouts outside the host autoloader still resolve. Cake names come
 * from the plugin `$name` default via reflection, falling back to the
 * namespace path — no plugin is ever instantiated.
 */
class CakePluginResolver
{
    /**
     * Resolve Cake plugins for packages and extra directories.
     *
     * Only `cakephp-plugin` packages with an existing install path are
     * considered. Packages and directories without a resolvable plugin
     * class are silently skipped. Each plugin class appears at most once.
     *
     * @param list<\Crustum\Inspector\Package> $packages Scanned packages
     * @param list<string> $extraPaths Additional plugin directories to scan
     * @return list<\Crustum\Inspector\CakePlugin>
     */
    public function resolve(array $packages, array $extraPaths = []): array
    {
        $resolved = [];
        $seenClasses = [];

        foreach ($packages as $package) {
            if ($package->type() !== 'cakephp-plugin') {
                continue;
            }

            $path = $package->path();
            if ($path === null || !is_dir($path)) {
                continue;
            }

            $detail = $this->fromPackage($package, $path);
            if ($detail instanceof CakePlugin && !isset($seenClasses[$detail->pluginClass()])) {
                $seenClasses[$detail->pluginClass()] = true;
                $resolved[] = $detail;
            }
        }

        foreach ($extraPaths as $path) {
            if (!is_dir($path)) {
                continue;
            }

            $detail = $this->fromDirectory($path);
            if ($detail instanceof CakePlugin && !isset($seenClasses[$detail->pluginClass()])) {
                $seenClasses[$detail->pluginClass()] = true;
                $resolved[] = $detail;
            }
        }

        return $resolved;
    }

    /**
     * Resolve a Cake plugin from a scanned package.
     *
     * @param \Crustum\Inspector\Package $package Scanned package
     * @param string $path Package install path
     * @return \Crustum\Inspector\CakePlugin|null
     */
    protected function fromPackage(Package $package, string $path): ?CakePlugin
    {
        $composerJson = $this->readComposerJson($path);
        $class = $this->resolveClass($path, $composerJson, $package->name());

        if ($class === null) {
            return null;
        }

        return new CakePlugin(
            package: $package->name(),
            class: $class,
            name: $this->cakeName($class, $package->name()),
            path: $path,
            version: $package->version(),
            direct: $package->isDirect(),
            dev: $package->isDev(),
        );
    }

    /**
     * Resolve a Cake plugin from an explicit directory.
     *
     * @param string $path Plugin directory
     * @return \Crustum\Inspector\CakePlugin|null
     */
    protected function fromDirectory(string $path): ?CakePlugin
    {
        $composerJson = $this->readComposerJson($path);
        if ($composerJson === null) {
            return null;
        }

        $composerName = $composerJson['name'] ?? null;
        $composerName = is_string($composerName) ? $composerName : '';

        $class = $this->resolveClass($path, $composerJson, $composerName);

        if ($class === null) {
            return null;
        }

        return new CakePlugin(
            package: $composerName,
            class: $class,
            name: $this->cakeName($class, $composerName),
            path: $path,
        );
    }

    /**
     * Resolve the plugin class for an installed package.
     *
     * @param string $packagePath Package install path
     * @param array<string, mixed>|null $composerJson Decoded package composer.json
     * @param string $composerName Composer package name
     * @return class-string|null
     */
    public function resolveClass(string $packagePath, ?array $composerJson, string $composerName): ?string
    {
        $candidates = [];

        $psr4 = $composerJson['autoload']['psr-4'] ?? null;
        if (is_array($psr4)) {
            foreach ($psr4 as $prefix => $dirs) {
                foreach ((array)$dirs as $dir) {
                    $base = rtrim(
                        $packagePath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, (string)$dir),
                        DIRECTORY_SEPARATOR,
                    );
                    if (!is_dir($base)) {
                        continue;
                    }

                    foreach ((array)glob($base . DIRECTORY_SEPARATOR . '*Plugin.php') as $file) {
                        $relative = substr((string)$file, strlen($base) + 1, -strlen('.php'));
                        $candidates[rtrim((string)$prefix, '\\') . '\\' . str_replace(DIRECTORY_SEPARATOR, '\\', $relative)] = $file;
                    }
                }
            }
        }

        if (str_contains($composerName, '/')) {
            [$vendor, $name] = explode('/', $composerName, 2);
            $vendorPart = Inflector::camelize(str_replace(['-', '_'], ' ', $vendor));
            $namePart = Inflector::camelize(str_replace(['-', '_'], ' ', $name));
            $candidates[$vendorPart . '\\' . $namePart . '\\' . $namePart . 'Plugin'] =
                $packagePath . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . $namePart . 'Plugin.php';
        }

        foreach ($candidates as $class => $file) {
            if (class_exists($class)) {
                return self::canonicalClass($class);
            }

            if (is_string($file) && is_file($file)) {
                try {
                    require_once $file;
                } catch (Throwable) {
                    continue;
                }

                if (class_exists($class)) {
                    return self::canonicalClass($class);
                }
            }
        }

        return null;
    }

    /**
     * Canonical plugin class name with declared casing.
     *
     * Guessed names may differ in letter case (PHP class names and some
     * filesystems are case-insensitive). Reflection reports the casing
     * from the class declaration, so downstream namespace and Cake name
     * derivation never inherit a miscased guess.
     *
     * @param class-string $class Resolved plugin class name
     * @return class-string Canonical plugin class name
     */
    protected static function canonicalClass(string $class): string
    {
        try {
            $canonical = (new ReflectionClass($class))->getName();
        } catch (Throwable) {
            return $class;
        }

        return class_exists($canonical) ? $canonical : $class;
    }

    /**
     * Derive the CakePHP plugin name without instantiating the plugin.
     *
     * Mirrors `BasePlugin::getName()`: explicit `$name` default wins,
     * otherwise the namespace path is used.
     *
     * @param class-string $pluginClass Plugin class name
     * @param string $composerName Composer package name
     * @return string
     */
    public function cakeName(string $pluginClass, string $composerName): string
    {
        try {
            $defaults = (new ReflectionClass($pluginClass))->getDefaultProperties();
        } catch (Throwable) {
            $defaults = [];
        }

        $name = $defaults['name'] ?? null;
        if (is_string($name) && $name !== '') {
            return $name;
        }

        $position = strrpos($pluginClass, '\\');
        if ($position !== false) {
            return str_replace('\\', '/', substr($pluginClass, 0, $position));
        }

        if (str_contains($composerName, '/')) {
            return substr($composerName, (int)strrpos($composerName, '/') + 1);
        }

        return $pluginClass;
    }

    /**
     * Read and decode a package composer.json file.
     *
     * @param string $packagePath Package install path
     * @return array<string, mixed>|null
     */
    protected function readComposerJson(string $packagePath): ?array
    {
        $file = rtrim($packagePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'composer.json';
        if (!is_file($file) || !is_readable($file)) {
            return null;
        }

        $contents = file_get_contents($file);
        if ($contents === false) {
            return null;
        }

        $json = json_decode($contents, true);

        return is_array($json) ? $json : null;
    }
}
