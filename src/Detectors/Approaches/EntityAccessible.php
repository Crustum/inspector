<?php
declare(strict_types=1);

namespace Crustum\Inspector\Detectors\Approaches;

use Crustum\Inspector\ApproachResult;
use Crustum\Inspector\Enums\Approach;
use Crustum\Inspector\Support\SourceFiles;

/**
 * Detects Entity accessible field conventions in CakePHP Entity classes.
 */
class EntityAccessible extends Convention
{
    /**
     * @inheritDoc
     */
    protected function result(SourceFiles $files): ?ApproachResult
    {
        return $this->electByFile($files, 'Model/Entity', function (string $contents): ?array {
            if (!str_contains($contents, '$_accessible')) {
                return null;
            }

            $hasWildcard = preg_match("/'\\*'\\s*=>/", $contents) === 1;

            return [
                Approach::EntityAccessibleDefault->value => $hasWildcard ? 1 : 0,
                Approach::EntityAccessibleWhitelist->value => $hasWildcard ? 0 : 1,
            ];
        });
    }
}
