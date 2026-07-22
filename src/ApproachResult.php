<?php
declare(strict_types=1);

namespace Crustum\Inspector;

use Crustum\Inspector\Enums\Approach;

/**
 * Result of a source-code convention detection vote.
 */
class ApproachResult
{
    /**
     * Constructor.
     *
     * @param \Crustum\Inspector\Enums\Approach $approach Winning approach
     * @param float $confidence Winning vote ratio
     * @param int $matched Votes for the winning style
     * @param int $total Total votes cast
     * @param list<string> $paths Files that contributed votes
     */
    public function __construct(
        public readonly Approach $approach,
        public readonly float $confidence,
        public readonly int $matched,
        public readonly int $total,
        public readonly array $paths,
    ) {
    }

    /**
     * Serialize to array for JSON output.
     *
     * @return array{
     *     approach: string,
     *     confidence: float,
     *     matched: int,
     *     total: int,
     *     paths: list<string>,
     * }
     */
    public function toArray(): array
    {
        return [
            'approach' => $this->approach->value,
            'confidence' => $this->confidence,
            'matched' => $this->matched,
            'total' => $this->total,
            'paths' => $this->paths,
        ];
    }
}
