<?php
declare(strict_types=1);

namespace Crustum\Inspector\Support;

use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use UnexpectedValueException;

/**
 * Indexes PHP source files under PSR-4 autoload roots.
 */
class SourceFiles
{
    /**
     * Base path with trailing separator.
     *
     * @var string
     */
    protected string $basePath;

    /**
     * Resolved source roots.
     *
     * @var list<string>|null
     */
    protected ?array $roots = null;

    /**
     * PHP files cached per root directory.
     *
     * @var array<string, list<string>>
     */
    protected array $filesByRoot = [];

    /**
     * PHP files cached per subpath filter.
     *
     * @var array<string, list<string>>
     */
    protected array $filesBySubpath = [];

    /**
     * Constructor.
     *
     * @param string $basePath Project base path
     */
    public function __construct(string $basePath)
    {
        $this->basePath = self::finishPath($basePath);
    }

    /**
     * PHP files optionally filtered by relative subpath.
     *
     * @param string|null $subpath Relative path prefix such as Model/Table
     * @return list<string>
     */
    public function php(?string $subpath = null): array
    {
        $subpath = trim(str_replace('\\', '/', (string)$subpath), '/');

        return $this->filesBySubpath[$subpath] ??= $this->resolvePhp($subpath === '' ? null : $subpath);
    }

    /**
     * Read file contents.
     *
     * @param string $path Absolute file path
     * @return string
     */
    public function contents(string $path): string
    {
        if (!is_file($path)) {
            return '';
        }

        $contents = file_get_contents($path);

        return $contents === false ? '' : $contents;
    }

    /**
     * Source roots for scanning.
     *
     * @return list<string>
     */
    public function roots(): array
    {
        return $this->roots ??= $this->resolveRoots();
    }

    /**
     * Resolve PHP files for a subpath filter.
     *
     * @param string|null $subpath Relative subpath filter
     * @return list<string>
     */
    protected function resolvePhp(?string $subpath): array
    {
        $pattern = $subpath === null
            ? null
            : '#(^|/)' . preg_quote($subpath, '#') . '/#';

        $files = [];

        foreach ($this->roots() as $root) {
            foreach ($this->phpFilesIn($root) as $file) {
                $relative = str_replace('\\', '/', substr($file, strlen($root) + 1));

                if ($pattern !== null && preg_match($pattern, $relative) !== 1) {
                    continue;
                }

                $files[realpath($file) ?: $file] = $file;
            }
        }

        $files = array_values($files);
        sort($files);

        return $files;
    }

    /**
     * Resolve scan roots from composer autoload and Cake conventions.
     *
     * @return list<string>
     */
    protected function resolveRoots(): array
    {
        $existing = [];

        foreach ([...$this->psr4Roots(), $this->basePath . 'src', ...$this->pluginRoots()] as $root) {
            $real = realpath(rtrim($root, '/\\'));

            if ($real === false) {
                continue;
            }

            if (!is_dir($real)) {
                continue;
            }

            $existing[$real] = $real;
        }

        return array_values($existing);
    }

    /**
     * PSR-4 autoload roots from composer.json.
     *
     * @return list<string>
     */
    protected function psr4Roots(): array
    {
        $path = $this->basePath . 'composer.json';

        if (!is_file($path)) {
            return [];
        }

        $data = json_decode((string)file_get_contents($path), true);

        if (!is_array($data)) {
            return [];
        }

        $autoload = $data['autoload'] ?? null;
        $psr4 = is_array($autoload) && is_array($autoload['psr-4'] ?? null) ? $autoload['psr-4'] : [];

        $roots = [];

        foreach ($psr4 as $paths) {
            foreach (is_array($paths) ? $paths : [$paths] as $relative) {
                if (is_string($relative) && $relative !== '') {
                    $roots[] = $this->basePath . str_replace('/', DIRECTORY_SEPARATOR, trim($relative, '/'));
                }
            }
        }

        return $roots;
    }

    /**
     * Plugin src roots under plugins src.
     *
     * @return list<string>
     */
    protected function pluginRoots(): array
    {
        $pluginsPath = $this->basePath . 'plugins';

        if (!is_dir($pluginsPath)) {
            return [];
        }

        $roots = [];

        foreach (scandir($pluginsPath) ?: [] as $entry) {
            if ($entry === '.') {
                continue;
            }

            if ($entry === '..') {
                continue;
            }

            $src = $pluginsPath . DIRECTORY_SEPARATOR . $entry . DIRECTORY_SEPARATOR . 'src';

            if (is_dir($src)) {
                $roots[] = $src;
            }
        }

        return $roots;
    }

    /**
     * Cached PHP files within a root.
     *
     * @param string $root Root directory
     * @return list<string>
     */
    protected function phpFilesIn(string $root): array
    {
        return $this->filesByRoot[$root] ??= $this->enumeratePhpFiles($root);
    }

    /**
     * Enumerate PHP files recursively under a root.
     *
     * @param string $root Root directory
     * @return list<string>
     */
    protected function enumeratePhpFiles(string $root): array
    {
        $files = [];

        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveCallbackFilterIterator(
                    new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS),
                    fn(SplFileInfo $file): bool => !$file->isDir()
                        || (!str_starts_with($file->getFilename(), '.') && !in_array($file->getFilename(), ['vendor', 'node_modules'], true)),
                ),
            );

            foreach ($iterator as $file) {
                if ($file instanceof SplFileInfo && $file->isFile() && strtolower($file->getExtension()) === 'php') {
                    $files[] = $file->getPathname();
                }
            }
        } catch (UnexpectedValueException) {
        }

        sort($files);

        return $files;
    }

    /**
     * Ensure path ends with directory separator.
     *
     * @param string $path Path to normalize
     * @return string
     */
    protected static function finishPath(string $path): string
    {
        return rtrim($path, DIRECTORY_SEPARATOR . '/') . DIRECTORY_SEPARATOR;
    }
}
