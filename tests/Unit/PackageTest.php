<?php

declare(strict_types=1);

use Crustum\Inspector\Enums\PackageSource;
use Crustum\Inspector\Package;

it('reports major version', function (): void {
    $package = new Package('vue', '3.4.0', PackageSource::Npm);

    expect($package->major())->toBe(3);
});

it('reports null major when the version is unknown', function (): void {
    $package = new Package('foo', '', PackageSource::Npm);

    expect($package->major())->toBeNull();
});

it('defaults the composer type to an empty string', function (): void {
    $package = new Package('vue', '3.4.0', PackageSource::Npm);

    expect($package->type())->toBe('');
});

it('exposes the composer type when provided', function (): void {
    $package = new Package('cakephp/queue', '5.0.0', PackageSource::Composer, type: 'cakephp-plugin');

    expect($package->type())->toBe('cakephp-plugin');
});

it('keeps the serialized shape stable for JSON consumers', function (): void {
    $package = new Package('cakephp/queue', '5.0.0', PackageSource::Composer, type: 'cakephp-plugin');

    expect(array_keys($package->toArray()))->toBe([
        'name',
        'version',
        'constraint',
        'direct',
        'dev',
        'source',
        'path',
    ]);
});
