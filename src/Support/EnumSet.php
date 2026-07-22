<?php
declare(strict_types=1);

namespace Crustum\Inspector\Support;

use BackedEnum;

/**
 * Set of detected backed enum cases.
 *
 * @template T of \BackedEnum
 */
class EnumSet
{
    /**
     * Constructor.
     *
     * @param list<T> $cases Enum cases
     */
    public function __construct(
        protected array $cases,
    ) {
    }

    /**
     * Whether any of the given cases is present.
     *
     * @param T|list<T> $value Case or list of cases
     * @return bool
     */
    public function uses(BackedEnum|array $value): bool
    {
        return array_any(is_array($value) ? $value : [$value], fn($needle): bool => in_array($needle, $this->cases, true));
    }

    /**
     * Whether all given cases are present.
     *
     * @param list<T> $values Cases to require
     * @return bool
     */
    public function usesAll(array $values): bool
    {
        return array_all($values, fn(BackedEnum $needle): bool => in_array($needle, $this->cases, true));
    }

    /**
     * All detected cases.
     *
     * @return list<T>
     */
    public function all(): array
    {
        return $this->cases;
    }

    /**
     * Backing values of detected cases.
     *
     * @return list<string|int>
     */
    public function values(): array
    {
        return array_map(fn(BackedEnum $case): int|string => $case->value, $this->cases);
    }
}
