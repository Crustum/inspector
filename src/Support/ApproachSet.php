<?php
declare(strict_types=1);

namespace Crustum\Inspector\Support;

use Cake\Collection\Collection;
use Crustum\Inspector\ApproachResult;
use Crustum\Inspector\Enums\Approach;

/**
 * Collection of detected approach convention results.
 */
class ApproachSet
{
    /**
     * Detection results.
     *
     * @var list<\Crustum\Inspector\ApproachResult>
     */
    protected array $results;

    /**
     * Constructor.
     *
     * @param list<\Crustum\Inspector\ApproachResult> $results Detection results
     */
    public function __construct(
        array $results,
    ) {
        $this->results = $results;
    }

    /**
     * Whether any of the given approaches was detected.
     *
     * @param \Crustum\Inspector\Enums\Approach|list<\Crustum\Inspector\Enums\Approach> $approach Approach case or list
     * @return bool
     */
    public function uses(Approach|array $approach): bool
    {
        return $this->approaches()->uses($approach);
    }

    /**
     * Whether all given approaches were detected.
     *
     * @param list<\Crustum\Inspector\Enums\Approach> $approaches Approach cases
     * @return bool
     */
    public function usesAll(array $approaches): bool
    {
        return $this->approaches()->usesAll($approaches);
    }

    /**
     * Result for a specific approach when detected.
     *
     * @param \Crustum\Inspector\Enums\Approach $approach Approach case
     */
    public function result(Approach $approach): ?ApproachResult
    {
        foreach ($this->results as $result) {
            if ($result->approach === $approach) {
                return $result;
            }
        }

        return null;
    }

    /**
     * All results keyed by approach value.
     *
     * @return \Cake\Collection\Collection<string, \Crustum\Inspector\ApproachResult>
     */
    public function all(): Collection
    {
        $items = [];

        foreach ($this->results as $result) {
            if (!array_key_exists($result->approach->value, $items)) {
                $items[$result->approach->value] = $result;
            }
        }

        return new Collection($items);
    }

    /**
     * Enum set of detected approaches.
     *
     * @return \Crustum\Inspector\Support\EnumSet<\Crustum\Inspector\Enums\Approach>
     */
    protected function approaches(): EnumSet
    {
        return new EnumSet(array_map(
            fn(ApproachResult $result): Approach => $result->approach,
            $this->results,
        ));
    }
}
