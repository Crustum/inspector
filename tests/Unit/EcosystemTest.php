<?php

declare(strict_types=1);

use Crustum\Inspector\Ecosystems\Ecosystem;
use Crustum\Inspector\PackageCollection;

it('finds a package by raw name', function (): void {
    $php = phpEcosystem([
        ['name' => 'pestphp/pest', 'dev' => true],
    ]);

    expect($php->uses('pestphp/pest'))->toBeTrue();
    expect($php->uses('phpunit/phpunit'))->toBeFalse();
});

it('compares versions through uses with semver constraints', function (): void {
    $php = phpEcosystem([
        ['name' => 'cakephp/cakephp', 'version' => '5.0.2'],
    ]);

    expect($php->uses('cakephp/cakephp', '>=5.0.0'))->toBeTrue();
    expect($php->uses('cakephp/cakephp', '>=6.0.0'))->toBeFalse();
    expect($php->uses('cakephp/cakephp', '<5.0.0'))->toBeFalse();
    expect($php->uses('cakephp/cakephp', '^5.0'))->toBeTrue();
    expect($php->uses('cakephp/cakephp', '~5.0.2'))->toBeTrue();
    expect($php->uses('cakephp/cakephp', '^6.0'))->toBeFalse();
    expect($php->uses('cakephp/cakephp', '>=5 <6'))->toBeTrue();
    expect($php->uses('cakephp/cakephp', '5.0.2'))->toBeTrue();
    expect($php->uses('cakephp/cakephp', '5.0.0'))->toBeFalse();
    expect($php->uses('unknown', '^1.0'))->toBeFalse();
});

it('throws on invalid semver constraint', function (): void {
    $php = new Ecosystem(new PackageCollection());

    expect(fn (): bool => $php->uses('foo', 'not-a-constraint'))->toThrow(InvalidArgumentException::class);
});

it('does not satisfy a constraint when the package version is empty', function (): void {
    $php = phpEcosystem([
        ['name' => 'vendor/dev-pkg', 'version' => ''],
    ]);

    expect($php->uses('vendor/dev-pkg'))->toBeTrue()
        ->and($php->uses('vendor/dev-pkg', '^1.0'))->toBeFalse()
        ->and($php->usesAll(['vendor/dev-pkg' => '^1.0']))->toBeFalse();
});

it('uses with array of names checks any-of', function (): void {
    $php = phpEcosystem([
        ['name' => 'pestphp/pest'],
    ]);

    expect($php->uses(['pestphp/pest', 'phpunit/phpunit']))->toBeTrue();
    expect($php->uses(['phpunit/phpunit', 'mockery/mockery']))->toBeFalse();
    expect($php->uses([]))->toBeFalse();
});

it('uses with assoc array applies per-package constraints', function (): void {
    $php = phpEcosystem([
        ['name' => 'pestphp/pest', 'version' => '3.8.1'],
        ['name' => 'cakephp/cakephp', 'version' => '5.0.2'],
    ]);

    expect($php->uses(['pestphp/pest' => '^3.0', 'cakephp/cakephp' => '^5.0']))->toBeTrue();
    expect($php->uses(['pestphp/pest' => '^4.0', 'cakephp/cakephp' => '^5.0']))->toBeTrue();
    expect($php->uses(['pestphp/pest' => '^4.0', 'cakephp/cakephp' => '^6.0']))->toBeFalse();
});

it('uses rejects mixed indexed/assoc arrays', function (): void {
    $php = phpEcosystem([['name' => 'pestphp/pest']]);

    expect(fn (): bool => $php->uses(['pestphp/pest', 'cakephp/cakephp' => '^5.0']))
        ->toThrow(InvalidArgumentException::class);
});

it('uses rejects array + constraint argument combination', function (): void {
    $php = phpEcosystem([['name' => 'pestphp/pest']]);

    expect(fn (): bool => $php->uses(['pestphp/pest'], '^3.0'))
        ->toThrow(InvalidArgumentException::class);
});

it('usesAll requires every listed package', function (): void {
    $php = phpEcosystem([
        ['name' => 'pestphp/pest', 'version' => '3.8.1'],
        ['name' => 'cakephp/cakephp', 'version' => '5.0.2'],
    ]);

    expect($php->usesAll(['pestphp/pest', 'cakephp/cakephp']))->toBeTrue();
    expect($php->usesAll(['pestphp/pest', 'phpunit/phpunit']))->toBeFalse();
    expect($php->usesAll([]))->toBeTrue();
});

it('usesAll applies per-package constraints', function (): void {
    $php = phpEcosystem([
        ['name' => 'pestphp/pest', 'version' => '3.8.1'],
        ['name' => 'cakephp/cakephp', 'version' => '5.0.2'],
    ]);

    expect($php->usesAll(['pestphp/pest' => '^3.0', 'cakephp/cakephp' => '^5.0']))->toBeTrue();
    expect($php->usesAll(['pestphp/pest' => '^4.0', 'cakephp/cakephp' => '^5.0']))->toBeFalse();
});

it('exposes dev / production / direct filters', function (): void {
    $php = phpEcosystem([
        ['name' => 'cakephp/cakephp'],
        ['name' => 'pestphp/pest', 'dev' => true],
        ['name' => 'phpstan/phpstan', 'direct' => false],
    ]);

    expect($php->packages()->production()->count())->toBe(2);
    expect($php->packages()->dev()->count())->toBe(1);
    expect($php->packages()->direct()->count())->toBe(2);
});

it('usesDirect checks direct dependencies including any-of arrays', function (): void {
    $php = phpEcosystem([
        ['name' => 'cakephp/cakephp'],
        ['name' => 'phpstan/phpstan', 'direct' => false],
    ]);

    expect($php->usesDirect('cakephp/cakephp'))->toBeTrue();
    expect($php->usesDirect('phpstan/phpstan'))->toBeFalse();
    expect($php->usesDirect('unknown/package'))->toBeFalse();
    expect($php->usesDirect(['phpstan/phpstan', 'cakephp/cakephp']))->toBeTrue();
    expect($php->usesDirect(['phpstan/phpstan', 'unknown/package']))->toBeFalse();
});

it('returns false instead of throwing when the stored version is invalid', function (): void {
    $php = phpEcosystem([
        ['name' => 'vendor/weird', 'version' => '1.2.02.0.0'],
    ]);

    expect($php->uses('vendor/weird'))->toBeTrue()
        ->and($php->uses('vendor/weird', '^1.2'))->toBeFalse();
});

it('keeps prerelease suffixes and evaluates them with composer semver', function (): void {
    $php = phpEcosystem([
        ['name' => 'vendor/pkg', 'version' => '1.0.0-beta.1'],
    ]);

    expect($php->uses('vendor/pkg', '1.0.0'))->toBeFalse()
        ->and($php->uses('vendor/pkg', '>=1.0.0'))->toBeTrue()
        ->and($php->uses('vendor/pkg', '>=1.0.0-beta'))->toBeTrue()
        ->and($php->uses('vendor/pkg', '1.0.0-beta.1'))->toBeTrue();
});

it('filters packages by composer type', function (): void {
    $php = phpEcosystem([
        ['name' => 'cakephp/queue', 'type' => 'cakephp-plugin'],
        ['name' => 'cakephp/cakephp', 'type' => 'library'],
        ['name' => 'vendor/plain'],
    ]);

    $plugins = $php->byType('cakephp-plugin');

    expect($plugins->count())->toBe(1)
        ->and($plugins->all()[0]->name())->toBe('cakephp/queue');
});

it('filters packages by several composer types', function (): void {
    $php = phpEcosystem([
        ['name' => 'cakephp/queue', 'type' => 'cakephp-plugin'],
        ['name' => 'vendor/bundle', 'type' => 'symfony-bundle'],
        ['name' => 'cakephp/cakephp', 'type' => 'library'],
    ]);

    expect($php->packages()->byTypes(['cakephp-plugin', 'symfony-bundle'])->count())->toBe(2)
        ->and($php->packages()->byType('unknown-type')->count())->toBe(0);
});

it('exposes installed cakephp plugins and type presence', function (): void {
    $php = phpEcosystem([
        ['name' => 'cakephp/queue', 'type' => 'cakephp-plugin'],
        ['name' => 'cakephp/cakephp', 'type' => 'library'],
    ]);

    expect($php->cakePlugins()->count())->toBe(1)
        ->and($php->cakePlugins()->all()[0]->name())->toBe('cakephp/queue')
        ->and($php->usesType('cakephp-plugin'))->toBeTrue()
        ->and($php->usesType('library'))->toBeTrue()
        ->and($php->usesType('unknown-type'))->toBeFalse();
});
