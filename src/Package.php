<?php
declare(strict_types=1);

namespace Crustum\Inspector;

use Crustum\Inspector\Enums\PackageSource;

/**
 * A detected package dependency with version and metadata.
 */
class Package
{
    /**
     * Constructor.
     *
     * @param string $name Package name from lock file or manifest
     * @param string $version Resolved version
     * @param \Crustum\Inspector\Enums\PackageSource $source Package ecosystem source
     * @param bool $dev Whether this is a dev dependency
     * @param bool $direct Whether this is a direct dependency
     * @param string $constraint Version constraint from manifest
     * @param string|null $path Filesystem path when known
     * @param string $type Composer package type (e.g. `library`, `cakephp-plugin`)
     */
    public function __construct(
        protected string $name,
        protected string $version,
        protected PackageSource $source,
        protected bool $dev = false,
        protected bool $direct = false,
        protected string $constraint = '',
        protected ?string $path = null,
        protected string $type = '',
    ) {
    }

    /**
     * Package name.
     *
     * @return string
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Resolved version string.
     *
     * @return string
     */
    public function version(): string
    {
        return $this->version;
    }

    /**
     * Major version segment when parseable.
     */
    public function major(): ?int
    {
        if ($this->version === '') {
            return null;
        }

        return (int)explode('.', $this->version)[0];
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
     * Whether this is a direct dependency.
     *
     * @return bool
     */
    public function isDirect(): bool
    {
        return $this->direct;
    }

    /**
     * Version constraint from manifest.
     *
     * @return string
     */
    public function constraint(): string
    {
        return $this->constraint;
    }

    /**
     * Package source.
     *
     * @return \Crustum\Inspector\Enums\PackageSource
     */
    public function source(): PackageSource
    {
        return $this->source;
    }

    /**
     * Filesystem path when set.
     */
    public function path(): ?string
    {
        return $this->path;
    }

    /**
     * Composer package type (e.g. `library`, `cakephp-plugin`).
     *
     * Empty string when the scanner did not provide type metadata
     * (e.g. JS packages or lock entries without a type).
     *
     * @return string
     */
    public function type(): string
    {
        return $this->type;
    }

    /**
     * Serialize to array for JSON output.
     *
     * Shape is intentionally stable for MCP/app consumers; use
     * {@see Package::type()} for type-aware filtering in PHP.
     *
     * @return array{
     *     name: string,
     *     version: string,
     *     constraint: string,
     *     direct: bool,
     *     dev: bool,
     *     source: string,
     *     path: string|null,
     * }
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'version' => $this->version,
            'constraint' => $this->constraint,
            'direct' => $this->direct,
            'dev' => $this->dev,
            'source' => $this->source->value,
            'path' => $this->path,
        ];
    }
}
