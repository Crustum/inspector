<?php

declare(strict_types=1);

use Cake\Log\Log;
use Crustum\Inspector\Enums\PackageSource;
use Crustum\Inspector\ProjectScan;
use Crustum\Inspector\Scanners\ComposerLock;

it('parses installed packages with raw names', function (): void {
    $packages = (new ComposerLock(testDirectory('cake-app')))->scan();

    $cakephp = inspectorCollection($packages)->first(fn ($p): bool => $p->name() === 'cakephp/cakephp');
    expect($cakephp)->not->toBeNull();
    expect($cakephp->version())->toEqual('5.3.6');
    expect($cakephp->isDev())->toBeFalse();
    expect($cakephp->isDirect())->toBeTrue();
    expect($cakephp->constraint())->toEqual('5.3.6');
    expect($cakephp->source())->toBe(PackageSource::Composer);
    expect($cakephp->path())->toEndWith('vendor' . DIRECTORY_SEPARATOR . 'cakephp' . DIRECTORY_SEPARATOR . 'cakephp');

    $pest = inspectorCollection($packages)->first(fn ($p): bool => $p->name() === 'pestphp/pest');
    expect($pest)->not->toBeNull();
    expect($pest->version())->toEqual('3.8.7');
    expect($pest->isDev())->toBeTrue();
});

it('strips composer version prefixes', function (): void {
    $base = tempBase();

    file_put_contents($base . 'composer.lock', json_encode([
        'packages' => [
            ['name' => 'crustum/notification', 'version' => 'v1.0.5'],
        ],
        'packages-dev' => [],
    ]));

    $packages = (new ComposerLock($base))->scan();

    $notification = inspectorCollection($packages)->first(fn ($p): bool => $p->name() === 'crustum/notification');
    expect($notification)->not->toBeNull();
    expect($notification->version())->toEqual('1.0.5');

    cleanup($base);
});

it('respects composer vendor-dir config', function (): void {
    $base = tempBase();

    file_put_contents($base . 'composer.json', json_encode([
        'require' => ['cakephp/cakephp' => '^5.0'],
        'config' => ['vendor-dir' => 'lib/packages'],
    ]));
    file_put_contents($base . 'composer.lock', json_encode([
        'packages' => [['name' => 'cakephp/cakephp', 'version' => 'v5.0.0']],
        'packages-dev' => [],
    ]));

    $packages = (new ComposerLock($base))->scan();
    $cakephp = inspectorCollection($packages)->first(fn ($p): bool => $p->name() === 'cakephp/cakephp');

    expect($cakephp->path())->toEndWith('lib' . DIRECTORY_SEPARATOR . 'packages' . DIRECTORY_SEPARATOR . 'cakephp' . DIRECTORY_SEPARATOR . 'cakephp');

    cleanup($base);
});

it('marks transitive dependencies as indirect', function (): void {
    $packages = (new ComposerLock(testDirectory('cake-app')))->scan();

    $cakephp = inspectorCollection($packages)->first(fn ($p): bool => $p->name() === 'cakephp/cakephp');
    expect($cakephp->isDirect())->toBeTrue();

    $brickMath = inspectorCollection($packages)->first(fn ($p): bool => $p->name() === 'brick/math');
    expect($brickMath)->not->toBeNull();
    expect($brickMath->isDirect())->toBeFalse();
});

it('returns an empty collection for a malformed composer.lock', function (): void {
    $base = tempBase();
    file_put_contents($base . 'composer.lock', '{truncated');

    expect((new ComposerLock($base))->scan())->toHaveCount(0);

    cleanup($base);
});

it('warns when composer.lock decodes but lacks a packages key', function (): void {
    $logFile = LOGS . 'inspector_composer_warn.log';
    if (is_file($logFile)) {
        unlink($logFile);
    }

    Log::drop('inspector_composer');
    Log::setConfig('inspector_composer', [
        'className' => 'File',
        'path' => LOGS,
        'file' => 'inspector_composer_warn',
        'scopes' => ['inspector'],
    ]);

    $base = tempBase();
    file_put_contents($base . 'composer.lock', json_encode(['packages-dev' => []]));

    $packages = (new ComposerLock($base))->scan();

    expect($packages)->toHaveCount(0)
        ->and(is_file($logFile))->toBeTrue()
        ->and((string)file_get_contents($logFile))->toContain('Malformed composer.lock');

    Log::drop('inspector_composer');
    cleanup($base);
});

it('classifies dev from the lockfile section even when require-dev disagrees', function (): void {
    $base = tempBase();

    file_put_contents($base . 'composer.lock', json_encode([
        'packages' => [
            ['name' => 'symfony/var-dumper', 'version' => 'v7.2.0'],
        ],
        'packages-dev' => [],
    ]));
    file_put_contents($base . 'composer.json', json_encode([
        'require-dev' => ['symfony/var-dumper' => '^7.0'],
    ]));

    $packages = (new ComposerLock($base))->scan();

    $dumper = inspectorCollection($packages)->first(fn ($p): bool => $p->name() === 'symfony/var-dumper');
    expect($dumper)->not->toBeNull()
        ->and($dumper->isDev())->toBeFalse()
        ->and($dumper->isDirect())->toBeTrue()
        ->and($dumper->constraint())->toEqual('^7.0');

    cleanup($base);
});

it('leaves the constraint blank for transitive packages and normalizes the version', function (): void {
    $base = tempBase();

    file_put_contents($base . 'composer.lock', json_encode([
        'packages' => [
            ['name' => 'phpstan/phpstan', 'version' => 'v2.1.0'],
        ],
        'packages-dev' => [],
    ]));
    file_put_contents($base . 'composer.json', json_encode([
        'require' => ['cakephp/cakephp' => '^5.0'],
    ]));

    $packages = (new ComposerLock($base))->scan();

    $phpstan = inspectorCollection($packages)->first(fn ($p): bool => $p->name() === 'phpstan/phpstan');
    expect($phpstan)->not->toBeNull()
        ->and($phpstan->isDirect())->toBeFalse()
        ->and($phpstan->version())->toEqual('2.1.0')
        ->and($phpstan->constraint())->toBeEmpty();

    cleanup($base);
});

it('finds the minimum PHP minor version from the root requirement', function (string $constraint, string $version): void {
    $base = tempBase();

    file_put_contents($base . 'composer.json', json_encode([
        'require' => ['php' => $constraint],
    ]));

    expect((new ComposerLock($base))->minimumPhpVersion())->toBe($version);

    cleanup($base);
})->with([
    'caret' => ['^8.3', '8.3'],
    'tilde with patch' => ['~8.2.5', '8.2'],
    'bounded range' => ['>=8.1 <8.5', '8.1'],
    'disjunction' => ['^8.2 || ^8.4', '8.2'],
    'exclusive lower bound' => ['>8.3.0', '8.3'],
    'wildcard minor' => ['8.4.*', '8.4'],
]);

it('does not infer a minimum PHP version from an unusable requirement', function (?string $constraint): void {
    $base = tempBase();

    if ($constraint !== null) {
        file_put_contents($base . 'composer.json', json_encode([
            'require' => ['php' => $constraint],
        ]));
    }

    expect((new ComposerLock($base))->minimumPhpVersion())->toBeNull();

    cleanup($base);
})->with([
    'missing manifest' => [null],
    'unbounded wildcard' => ['*'],
    'upper bound only' => ['<8.4'],
    'development branch' => ['dev-main'],
    'invalid constraint' => ['not-a-version'],
]);

it('populates the composer type from lock entries', function (): void {
    $packages = (new ComposerLock(testDirectory('cake-app')))->scan();

    $queue = inspectorCollection($packages)->first(fn ($p): bool => $p->name() === 'cakephp/queue');
    expect($queue)->not->toBeNull()
        ->and($queue->type())->toBe('cakephp-plugin');

    $cakephp = inspectorCollection($packages)->first(fn ($p): bool => $p->name() === 'cakephp/cakephp');
    expect($cakephp)->not->toBeNull()
        ->and($cakephp->type())->toBe('library');
});

it('exposes cakephp plugins from a scanned project', function (): void {
    $project = ProjectScan::scan(testDirectory('cake-app'));

    $names = array_map(fn ($p) => $p->name(), $project->php()->cakePlugins()->all());

    expect($names)->toContain('cakephp/queue')
        ->and($project->php()->usesType('cakephp-plugin'))->toBeTrue();
});

it('falls back to the installed package composer.json when the lock entry has no type', function (): void {
    $base = tempBase();

    mkdir($base . 'vendor' . DIRECTORY_SEPARATOR . 'acme', 0777, true);
    mkdir($base . 'vendor' . DIRECTORY_SEPARATOR . 'acme' . DIRECTORY_SEPARATOR . 'plugin', 0777, true);
    file_put_contents(
        $base . 'vendor' . DIRECTORY_SEPARATOR . 'acme' . DIRECTORY_SEPARATOR . 'plugin' . DIRECTORY_SEPARATOR . 'composer.json',
        json_encode(['name' => 'acme/plugin', 'type' => 'cakephp-plugin']),
    );
    file_put_contents($base . 'composer.lock', json_encode([
        'packages' => [['name' => 'acme/plugin', 'version' => 'v1.2.0']],
        'packages-dev' => [],
    ]));

    $packages = (new ComposerLock($base))->scan();
    $plugin = inspectorCollection($packages)->first(fn ($p): bool => $p->name() === 'acme/plugin');

    expect($plugin)->not->toBeNull()
        ->and($plugin->type())->toBe('cakephp-plugin')
        ->and($plugin->version())->toBe('1.2.0');

    cleanup($base);
});

it('leaves the type empty when neither lock nor installed metadata provides one', function (): void {
    $base = tempBase();

    file_put_contents($base . 'composer.lock', json_encode([
        'packages' => [['name' => 'acme/unknown', 'version' => 'v1.0.0']],
        'packages-dev' => [],
    ]));

    $packages = (new ComposerLock($base))->scan();
    $package = inspectorCollection($packages)->first(fn ($p): bool => $p->name() === 'acme/unknown');

    expect($package)->not->toBeNull()
        ->and($package->type())->toBe('');

    cleanup($base);
});
