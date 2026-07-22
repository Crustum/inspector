<?php
declare(strict_types=1);

namespace Tests\Support;

use Cake\Collection\Collection as CakeCollection;
use Cake\Collection\CollectionInterface;

/**
 * Extended collection helpers.
 */
class ExtendedCollection extends CakeCollection
{
    /**
     * Wrap a CakePHP collection or iterable for callback-based queries.
     *
     * @param \Cake\Collection\CollectionInterface|iterable<mixed> $items Source items
     */
    public static function wrap(CollectionInterface|iterable $items): self
    {
        if ($items instanceof self) {
            return $items;
        }

        if ($items instanceof CollectionInterface) {
            return new self($items->toList());
        }

        return new self($items);
    }

    /**
     * Return the first element, optionally matching a callback.
     *
     * @param callable|null $callback Optional filter callback
     */
    #[\Override]
    public function first(?callable $callback = null): mixed
    {
        if ($callback === null) {
            return parent::first();
        }

        foreach ($this as $key => $value) {
            if ($callback($value, $key)) {
                return $value;
            }
        }

        return null;
    }

    /**
     * Determine if an item is in the collection.
     *
     * @param mixed $value Value or callback to match
     * @return bool
     */
    #[\Override]
    public function contains(mixed $value): bool
    {
        if (is_callable($value)) {
            foreach ($this as $item) {
                if ($value($item)) {
                    return true;
                }
            }

            return false;
        }

        return parent::contains($value);
    }
}
