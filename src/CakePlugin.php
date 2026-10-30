<?php
declare(strict_types=1);

namespace Crustum\Inspector;

/**
 * A resolved CakePHP plugin: composer package + plugin class + Cake name.
 */
class CakePlugin
{
    /**
     * Constructor.
     *
     * @param string $package Composer package name
     * @param string $class Plugin class name
     * @param string $name CakePHP plugin name
     * @param string $path Plugin install path
     * @param string $version Resolved package version
     * @param bool $direct Whether this is a direct dependency
     * @param bool $dev Whether this is a dev dependency
     */
    public function __construct(
        protected string $package,
        protected string $class,
        protected string $name,
        protected string $path,
        protected string $version = '',
        protected bool $direct = true,
        protected bool $dev = false,
    ) {
    }

    /**
     * Composer package name.
     *
     * @return string
     */
    public function package(): string
    {
        return $this->package;
    }

    /**
     * Plugin class name.
     *
     * @return string
     */
    public function pluginClass(): string
    {
        return $this->class;
    }

    /**
     * CakePHP plugin name.
     *
     * @return string
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Plugin install path.
     *
     * @return string
     */
    public function path(): string
    {
        return $this->path;
    }

    /**
     * Resolved package version.
     *
     * @return string
     */
    public function version(): string
    {
        return $this->version;
    }

    /**
     * Whether this is a direct dependency.
     *
     * @return bool
     */
    public function isDirect(): bool
    {
        return $this->direct;
    }

    /**
     * Whether this is a dev dependency.
     *
     * @return bool
     */
    public function isDev(): bool
    {
        return $this->dev;
    }

    /**
     * Serialize to array.
     *
     * @return array{
     *     package: string,
     *     class: string,
     *     name: string,
     *     path: string,
     *     version: string,
     *     direct: bool,
     *     dev: bool,
     * }
     */
    public function toArray(): array
    {
        return [
            'package' => $this->package,
            'class' => $this->class,
            'name' => $this->name,
            'path' => $this->path,
            'version' => $this->version,
            'direct' => $this->direct,
            'dev' => $this->dev,
        ];
    }
}
