<?php
declare(strict_types=1);

namespace Crustum\Inspector\Detectors\Approaches;

use Crustum\Inspector\ApproachResult;
use Crustum\Inspector\Enums\Approach;
use Crustum\Inspector\Support\SourceFiles;

/**
 * Base class for source-code convention detectors.
 */
abstract class Convention
{
    protected const MIN_SAMPLE = 3;

    protected const CONFIDENCE_FLOOR = 0.8;

    /**
     * Detect convention results.
     *
     * @param \Crustum\Inspector\Support\SourceFiles $files Source file index
     * @return list<\Crustum\Inspector\ApproachResult>
     */
    public function detect(SourceFiles $files): array
    {
        $result = $this->result($files);

        return $result instanceof ApproachResult ? [$result] : [];
    }

    /**
     * Compute a single convention result.
     *
     * @param \Crustum\Inspector\Support\SourceFiles $files Source file index
     */
    protected function result(SourceFiles $files): ?ApproachResult
    {
        return null;
    }

    /**
     * Vote per file then aggregate across the codebase.
     *
     * @param \Crustum\Inspector\Support\SourceFiles $files Source file index
     * @param string|null $in Relative subpath filter
     * @param callable(string): (array<string, int>|null) $counts File contents counter
     */
    protected function electByFile(SourceFiles $files, ?string $in, callable $counts): ?ApproachResult
    {
        $tally = [];
        $paths = [];

        foreach ($files->php($in) as $path) {
            $fileCounts = $counts($files->contents($path));

            if ($fileCounts === null) {
                continue;
            }

            $winner = $this->fileVote($fileCounts);

            if ($winner === null) {
                continue;
            }

            $tally[$winner] = ($tally[$winner] ?? 0) + 1;
            $paths[] = $path;
        }

        return $this->dominant($tally, $paths);
    }

    /**
     * Pick the winning style within one file.
     *
     * @param array<string, int> $counts Style occurrence counts
     */
    protected function fileVote(array $counts): ?string
    {
        $counts = array_filter($counts, fn(int $count): bool => $count > 0);

        if ($counts === []) {
            return null;
        }

        arsort($counts);
        $ranked = array_values($counts);

        if (isset($ranked[1]) && $ranked[1] === $ranked[0]) {
            return null;
        }

        return array_key_first($counts);
    }

    /**
     * Aggregate file votes into a confident result.
     *
     * @param array<string, int> $tally Style vote counts
     * @param list<string> $paths Contributing file paths
     */
    protected function dominant(array $tally, array $paths): ?ApproachResult
    {
        $tally = array_filter($tally, fn(int $votes): bool => $votes > 0);
        $total = array_sum($tally);

        if ($total < static::MIN_SAMPLE) {
            return null;
        }

        arsort($tally);
        $winner = (string)array_key_first($tally);
        $votes = $tally[$winner];

        if ($votes / $total < static::CONFIDENCE_FLOOR) {
            return null;
        }

        return new ApproachResult(
            approach: Approach::from($winner),
            confidence: $votes / $total,
            matched: $votes,
            total: $total,
            paths: $paths,
        );
    }
}
