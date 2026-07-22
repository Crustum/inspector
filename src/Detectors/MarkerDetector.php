<?php
declare(strict_types=1);

namespace Crustum\Inspector\Detectors;

use BackedEnum;

/**
 * Base detector for filesystem marker presence.
 *
 * @template TEnum of \BackedEnum
 */
abstract class MarkerDetector
{
    /**
     * @var class-string<TEnum>
     */
    protected const ENUM = BackedEnum::class;

    /**
     * Marker paths per enum value.
     *
     * @return array<string, list<string>>
     */
    abstract protected static function projectMarkers(): array;

    /**
     * Detect markers under a project base path.
     *
     * @param string $basePath Project base path
     * @return list<TEnum>
     */
    public static function detect(string $basePath): array
    {
        $detected = [];

        foreach (static::projectMarkers() as $value => $markers) {
            foreach ($markers as $marker) {
                if (self::markerMatches($basePath, $marker)) {
                    $detected[] = static::ENUM::from((string)$value);

                    break;
                }
            }
        }

        return $detected;
    }

    /**
     * All marker paths used by this detector.
     *
     * @return list<string>
     */
    public static function markerPaths(): array
    {
        $paths = [];

        foreach (static::projectMarkers() as $markers) {
            foreach ($markers as $marker) {
                $paths[] = $marker;
            }
        }

        return array_values(array_unique($paths));
    }

    /**
     * Whether a marker exists under the base path.
     *
     * @param string $basePath Project base path
     * @param string $marker Relative marker path or glob
     * @return bool
     */
    public static function markerMatches(string $basePath, string $marker): bool
    {
        $relative = str_replace('/', DIRECTORY_SEPARATOR, $marker);

        if (!str_contains($marker, '*')) {
            return file_exists($basePath . $relative);
        }

        $directory = dirname($relative) === '.'
            ? rtrim($basePath, DIRECTORY_SEPARATOR)
            : $basePath . dirname($relative);
        $pattern = basename($relative);

        if (!is_dir($directory)) {
            return false;
        }

        return array_any(scandir($directory) ?: [], fn(string $entry): bool => $entry !== '.' && $entry !== '..' && fnmatch($pattern, $entry));
    }
}
