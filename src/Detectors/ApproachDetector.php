<?php
declare(strict_types=1);

namespace Crustum\Inspector\Detectors;

use Crustum\Inspector\Detectors\Approaches\EntityAccessible;
use Crustum\Inspector\Detectors\Approaches\EnumCasing;
use Crustum\Inspector\Support\SourceFiles;

/**
 * Detects source-code conventions across PHP files.
 */
class ApproachDetector
{
    /**
     * Constructor.
     *
     * @param \Crustum\Inspector\Support\SourceFiles $files Source file index
     */
    public function __construct(
        protected SourceFiles $files,
    ) {
    }

    /**
     * Detect all configured conventions.
     *
     * @param string $basePath Project base path
     * @return list<\Crustum\Inspector\ApproachResult>
     */
    public static function detect(string $basePath): array
    {
        return (new self(new SourceFiles($basePath)))->all();
    }

    /**
     * Convention detectors to run.
     *
     * @return list<\Crustum\Inspector\Detectors\Approaches\Convention>
     */
    protected function conventions(): array
    {
        return [
            new EnumCasing(),
            new EntityAccessible(),
        ];
    }

    /**
     * Run all convention detectors.
     *
     * @return list<\Crustum\Inspector\ApproachResult>
     */
    public function all(): array
    {
        $results = [];

        foreach ($this->conventions() as $convention) {
            $results = [...$results, ...$convention->detect($this->files)];
        }

        return $results;
    }
}
