<?php
declare(strict_types=1);

namespace Crustum\Inspector;

use Countable;
use IteratorAggregate;
use Traversable;

/**
 * Mutable collection of detected packages.
 *
 * @implements \IteratorAggregate<int, \Crustum\Inspector\Package>
 * @phpstan-consistent-constructor
 */
class PackageCollection implements Countable, IteratorAggregate
{
    /**
     * Constructor.
     *
     * @param list<\Crustum\Inspector\Package> $items Package items
     */
    public function __construct(
        protected array $items = [],
    ) {
    }

    /**
     * Append a package.
     *
     * @param \Crustum\Inspector\Package $package Package instance
     * @return void
     */
    public function push(Package $package): void
    {
        $this->items[] = $package;
    }

    /**
     * All packages.
     *
     * @return list<\Crustum\Inspector\Package>
     */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * Dev dependencies only.
     */
    public function dev(): static
    {
        return new static(array_values(array_filter(
            $this->items,
            fn(Package $package): bool => $package->isDev(),
        )));
    }

    /**
     * Production dependencies only.
     */
    public function production(): static
    {
        return new static(array_values(array_filter(
            $this->items,
            fn(Package $package): bool => !$package->isDev(),
        )));
    }

    /**
     * Direct dependencies only.
     */
    public function direct(): static
    {
        return new static(array_values(array_filter(
            $this->items,
            fn(Package $package): bool => $package->isDirect(),
        )));
    }

    /**
     * Packages matching a composer type (e.g. `cakephp-plugin`).
     *
     * @param string $type Package type to match
     * @return static
     */
    public function byType(string $type): static
    {
        return $this->byTypes([$type]);
    }

    /**
     * Packages matching any of the given composer types.
     *
     * @param list<string> $types Package types to match
     * @return static
     */
    public function byTypes(array $types): static
    {
        return new static(array_values(array_filter(
            $this->items,
            fn(Package $package): bool => in_array($package->type(), $types, true),
        )));
    }

    /**
     * @inheritDoc
     */
    public function getIterator(): Traversable
    {
        yield from $this->items;
    }

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        return count($this->items);
    }
}
