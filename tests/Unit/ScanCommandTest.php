<?php

declare(strict_types=1);

use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;

uses(TestCase::class, ConsoleIntegrationTestTrait::class);

beforeEach(function (): void {
    $this->setAppNamespace();

    if (!class_exists(\TestApp\Application::class)) {
        require_once ROOT . DS . 'tests' . DS . 'TestApp' . DS . 'Application.php';
    }

    $this->configApplication(\TestApp\Application::class, [CONFIG]);
});

it('outputs JSON for directory with packages', function (): void {
    $path = realpath(testDirectory('cake-app'));
    expect($path)->not->toBeFalse();

    $this->exec('inspector scan ' . escapeshellarg($path));

    $this->assertExitSuccess();
    $output = implode('', $this->out->messages());
    $decoded = json_decode($output, true);

    expect($decoded)->toBeArray();
    expect($decoded)->toHaveKey('php');
    expect($decoded)->toHaveKey('js');
    expect(count($decoded['php']))->toBeGreaterThan(0);
});

it('outputs empty JSON for empty directory', function (): void {
    $emptyDir = tempBase();

    $this->exec('inspector scan ' . escapeshellarg($emptyDir));

    $this->assertExitSuccess();
    $output = implode('', $this->out->messages());
    $decoded = json_decode($output, true);

    expect($decoded)->toBeArray();
    expect($decoded['php'])->toBe([]);
    expect($decoded['js'])->toBe([]);

    cleanup($emptyDir);
});

it('includes approaches only when requested', function (): void {
    $path = realpath(testDirectory('approaches/accessible-whitelist-entities-app'));
    expect($path)->not->toBeFalse();

    $this->exec('inspector scan ' . escapeshellarg($path));

    $this->assertExitSuccess();
    $decoded = json_decode(implode('', $this->out->messages()), true);

    expect($decoded)->not->toHaveKey('approaches');

    $this->exec('inspector scan ' . escapeshellarg($path) . ' --approaches');

    $this->assertExitSuccess();
    $decoded = json_decode(implode('', $this->out->messages()), true);

    expect($decoded)->toHaveKey('approaches');
    expect($decoded['approaches'])->toHaveKey('entity-accessible-whitelist');
    expect($decoded['approaches']['entity-accessible-whitelist']['matched'])->toBeGreaterThanOrEqual(5);
});

it('encodes a whole confidence as a float so the field keeps one json type', function (): void {
    $path = realpath(testDirectory('approaches/accessible-whitelist-entities-app'));
    expect($path)->not->toBeFalse();

    $this->exec('inspector scan ' . escapeshellarg($path) . ' --approaches');

    $this->assertExitSuccess();
    $output = implode('', $this->out->messages());

    expect($output)->toContain('"confidence": 1.0')
        ->and($output)->not->toContain('"confidence": 1,');
});

it('defaults to the working directory when no directory is given', function (): void {
    $this->exec('inspector scan');

    $this->assertExitSuccess();
    $decoded = json_decode(implode('', $this->out->messages()), true);

    expect($decoded)->toBeArray();
    expect($decoded)->toHaveKey('php');
});

it('returns failure for non-existent directory', function (): void {
    $nonExistentDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'inspector_nonexistent_' . uniqid();

    $this->exec('inspector scan ' . escapeshellarg($nonExistentDir));

    $this->assertExitError();
});

it('returns failure for invalid directory argument', function (): void {
    $this->exec('inspector scan 123');

    $this->assertExitError();
});

it('accepts --cached to use the inspector cache store', function (): void {
    inspectorUseArrayCache();

    $path = realpath(testDirectory('cake-app'));
    expect($path)->not->toBeFalse();

    $this->exec('inspector scan ' . escapeshellarg($path) . ' --cached');

    $this->assertExitSuccess();
    $decoded = json_decode(implode('', $this->out->messages()), true);

    expect($decoded)->toBeArray();
    expect($decoded)->toHaveKey('php');
});
