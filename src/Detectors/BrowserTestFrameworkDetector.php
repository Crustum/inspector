<?php
declare(strict_types=1);

namespace Crustum\Inspector\Detectors;

use Crustum\Inspector\Ecosystems\Ecosystem;
use Crustum\Inspector\Ecosystems\JsEcosystem;
use Crustum\Inspector\Enums\BrowserTestFramework;
use Crustum\Inspector\Enums\PackageSource;

/**
 * Detects browser test frameworks from packages and config markers.
 */
class BrowserTestFrameworkDetector
{
    /**
     * @var list<array{framework: \Crustum\Inspector\Enums\BrowserTestFramework, ecosystem: \Crustum\Inspector\Enums\PackageSource, package: string, markers: list<string>}>
     */
    private const RULES = [
        [
            'framework' => BrowserTestFramework::Playwright,
            'ecosystem' => PackageSource::Npm,
            'package' => '@playwright/test',
            'markers' => ['playwright.config.ts', 'playwright.config.js', 'playwright.config.mjs', 'playwright.config.cjs'],
        ],
        [
            'framework' => BrowserTestFramework::Cypress,
            'ecosystem' => PackageSource::Npm,
            'package' => 'cypress',
            'markers' => ['cypress.config.ts', 'cypress.config.js', 'cypress.config.mjs', 'cypress.config.cjs', 'cypress.json'],
        ],
        [
            'framework' => BrowserTestFramework::Codeception,
            'ecosystem' => PackageSource::Composer,
            'package' => 'codeception/codeception',
            'markers' => ['codeception.yml', 'tests/Acceptance'],
        ],
    ];

    /**
     * Detect browser test frameworks in use.
     *
     * @param \Crustum\Inspector\Ecosystems\Ecosystem $php PHP ecosystem
     * @param \Crustum\Inspector\Ecosystems\JsEcosystem $js JS ecosystem
     * @param string $basePath Project base path
     * @return list<\Crustum\Inspector\Enums\BrowserTestFramework>
     */
    public static function detect(Ecosystem $php, JsEcosystem $js, string $basePath): array
    {
        $found = [];

        foreach (self::RULES as $rule) {
            $ecosystem = $rule['ecosystem'] === PackageSource::Composer ? $php : $js;

            if ($ecosystem->usesDirect($rule['package']) && self::markersPresent($basePath, $rule['markers'])) {
                $found[] = $rule['framework'];
            }
        }

        return $found;
    }

    /**
     * Marker paths referenced by detection rules.
     *
     * @return list<string>
     */
    public static function markerPaths(): array
    {
        return array_merge(...array_column(self::RULES, 'markers'));
    }

    /**
     * Whether any marker exists under the base path.
     *
     * @param string $basePath Project base path
     * @param list<string> $markers Relative marker paths
     * @return bool
     */
    private static function markersPresent(string $basePath, array $markers): bool
    {
        if ($markers === []) {
            return true;
        }

        return array_any($markers, fn($marker): bool => file_exists($basePath . str_replace('/', DIRECTORY_SEPARATOR, $marker)));
    }
}
