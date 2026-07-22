<?php
declare(strict_types=1);

namespace Crustum\Inspector\Detectors\Approaches;

use Crustum\Inspector\ApproachResult;
use Crustum\Inspector\Enums\Approach;
use Crustum\Inspector\Support\SourceFiles;

/**
 * Detects enum case naming style across PHP enums.
 */
class EnumCasing extends Convention
{
    /**
     * @inheritDoc
     */
    protected function result(SourceFiles $files): ?ApproachResult
    {
        $tally = [
            Approach::EnumCaseScreamingSnake->value => 0,
            Approach::EnumCasePascal->value => 0,
            Approach::EnumCaseCamel->value => 0,
        ];

        $paths = [];

        foreach ($files->php() as $path) {
            $contents = $files->contents($path);

            if (stripos($contents, 'enum') === false) {
                continue;
            }

            $names = $this->enumCaseNames($contents);

            if ($names === []) {
                continue;
            }

            $votes = 0;

            foreach ($names as $name) {
                $style = $this->classifyCase($name);

                if (!$style instanceof Approach) {
                    continue;
                }

                match ($style) {
                    Approach::EnumCaseScreamingSnake => $tally[Approach::EnumCaseScreamingSnake->value]++,
                    Approach::EnumCasePascal => $tally[Approach::EnumCasePascal->value]++,
                    Approach::EnumCaseCamel => $tally[Approach::EnumCaseCamel->value]++,
                };
                $votes++;
            }

            if ($votes > 0) {
                $paths[] = $path;
            }
        }

        return $this->dominant($tally, $paths);
    }

    /**
     * Extract enum case names from PHP source.
     *
     * @param string $code PHP source
     * @return list<string>
     */
    protected function enumCaseNames(string $code): array
    {
        preg_match_all('/^\s*case\s+(\w+)\s*[;=]/m', $code, $matches);

        return $matches[1];
    }

    /**
     * Classify an enum case name into a casing style.
     *
     * @param string $name Case name
     * @return \Crustum\Inspector\Enums\Approach::EnumCaseScreamingSnake|\Crustum\Inspector\Enums\Approach::EnumCasePascal|\Crustum\Inspector\Enums\Approach::EnumCaseCamel|null
     */
    protected function classifyCase(string $name): ?Approach
    {
        if (preg_match('/^[A-Z0-9]+(_[A-Z0-9]+)*$/', $name) === 1 && preg_match('/[A-Z]/', $name) === 1) {
            return Approach::EnumCaseScreamingSnake;
        }

        if (preg_match('/^[A-Z][a-zA-Z0-9]*$/', $name) === 1) {
            return Approach::EnumCasePascal;
        }

        if (preg_match('/^[a-z][a-zA-Z0-9]*$/', $name) === 1) {
            return Approach::EnumCaseCamel;
        }

        return null;
    }
}
