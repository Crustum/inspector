<?php

declare(strict_types=1);

use Crustum\Inspector\Detectors\BrowserTestFrameworkDetector;
use Crustum\Inspector\Enums\BrowserTestFramework;

it('detects a framework only when its package and marker are both present', function (): void {
    $base = tempBase();
    mkdir($base . 'tests/Acceptance', 0777, true);
    touchFile($base . 'playwright.config.ts');
    touchFile($base . 'cypress.config.ts');
    touchFile($base . 'codeception.yml');

    $found = BrowserTestFrameworkDetector::detect(
        phpEcosystem(['codeception/codeception']),
        jsEcosystem(['@playwright/test', 'cypress']),
        $base,
    );

    expect($found)->toContain(BrowserTestFramework::Playwright);
    expect($found)->toContain(BrowserTestFramework::Cypress);
    expect($found)->toContain(BrowserTestFramework::Codeception);
    expect($found)->toHaveCount(3);

    cleanup($base);
});

it('ignores a package with no confirming marker', function (): void {
    $base = tempBase();

    $found = BrowserTestFrameworkDetector::detect(
        phpEcosystem(['codeception/codeception']),
        jsEcosystem(['@playwright/test']),
        $base,
    );

    expect($found)->toBe([]);

    cleanup($base);
});

it('ignores a transitive package even when its config is present', function (): void {
    $base = tempBase();
    touchFile($base . 'playwright.config.ts');

    $found = BrowserTestFrameworkDetector::detect(
        phpEcosystem([]),
        jsEcosystem([['name' => '@playwright/test', 'direct' => false]]),
        $base,
    );

    expect($found)->toBe([]);

    cleanup($base);
});
