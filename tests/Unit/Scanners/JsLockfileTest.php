<?php

declare(strict_types=1);

use Crustum\Inspector\Enums\JsPackageManager;
use Crustum\Inspector\Scanners\JsLockfile;

it('scans package-lock.json when present', function (): void {
    $base = fixtureCopy([
        'cake-app/package.json' => 'package.json',
        'cake-app/package-lock.json' => 'package-lock.json',
    ]);

    $packages = (new JsLockfile($base))->scan();

    $tailwind = inspectorCollection($packages)->first(fn ($p): bool => $p->name() === 'tailwindcss');
    expect($tailwind->version())->toEqual('3.4.16');
    expect($tailwind->path())->toEndWith('node_modules' . DIRECTORY_SEPARATOR . 'tailwindcss');

    $echoReact = inspectorCollection($packages)->first(fn ($p): bool => $p->name() === '@laravel/echo-react');
    expect($echoReact)->not->toBeNull();

    cleanup($base);
});

it('marks transitive dev-only npm packages as dev', function (): void {
    $base = fixtureCopy([
        'cake-app/package.json' => 'package.json',
        'cake-app/package-lock.json' => 'package-lock.json',
    ]);

    $packages = (new JsLockfile($base))->scan();

    $quickLru = inspectorCollection($packages)->first(fn ($p): bool => $p->name() === '@alloc/quick-lru');
    expect($quickLru->isDev())->toBeTrue();
    expect($quickLru->isDirect())->toBeFalse();

    $codeFrame = inspectorCollection($packages)->first(fn ($p): bool => $p->name() === '@babel/code-frame');
    expect($codeFrame->isDev())->toBeFalse();
    expect($codeFrame->isDirect())->toBeFalse();

    cleanup($base);
});

it('indexes npm transitives nested under another dependency', function (): void {
    $base = fixtureCopy([
        'cake-app/package.json' => 'package.json',
        'cake-app/package-lock.json' => 'package-lock.json',
    ]);

    $packages = (new JsLockfile($base))->scan();

    $specificity = inspectorCollection($packages)->first(fn ($p): bool => $p->name() === '@csstools/selector-specificity');
    expect($specificity->version())->toEqual('3.1.1');
    expect($specificity->isDev())->toBeTrue();
    expect($specificity->isDirect())->toBeFalse();

    $globParent = inspectorCollection($packages)->first(fn ($p): bool => $p->name() === 'glob-parent');
    expect($globParent->version())->toEqual('6.0.2');

    cleanup($base);
});

it('prefers package-lock.json when multiple lockfiles are committed', function (): void {
    $base = fixtureCopy([
        'cake-app/package.json' => 'package.json',
        'cake-app/package-lock.json' => 'package-lock.json',
        'cake-app/pnpm-lock.yaml' => 'pnpm-lock.yaml',
    ]);

    $lockfile = new JsLockfile($base);

    expect($lockfile->committedManager())->toBe(JsPackageManager::Npm);

    $tailwind = inspectorCollection($lockfile->scan())->first(fn ($p): bool => $p->name() === 'tailwindcss');
    expect($tailwind->version())->toEqual('3.4.16');

    cleanup($base);
});

it('scans pnpm-lock.yaml when it is the committed lockfile', function (): void {
    $base = fixtureCopy([
        'cake-app/package.json' => 'package.json',
        'cake-app/pnpm-lock.yaml' => 'pnpm-lock.yaml',
    ]);

    $lockfile = new JsLockfile($base);

    expect($lockfile->committedManager())->toBe(JsPackageManager::Pnpm);

    $tailwind = inspectorCollection($lockfile->scan())->first(fn ($p): bool => $p->name() === 'tailwindcss');
    expect($tailwind->version())->toEqual('3.4.3');

    cleanup($base);
});

it('scans yarn.lock when it is the committed lockfile', function (): void {
    $base = fixtureCopy([
        'cake-app/package.json' => 'package.json',
        'cake-app/yarn.lock' => 'yarn.lock',
    ]);

    $lockfile = new JsLockfile($base);

    expect($lockfile->committedManager())->toBe(JsPackageManager::Yarn);

    $parser = inspectorCollection($lockfile->scan())->first(fn ($p): bool => $p->name() === '@babel/parser');
    expect($parser->version())->toEqual('7.28.5');

    cleanup($base);
});

it('scans bun.lock when it is the committed lockfile', function (): void {
    $base = fixtureCopy([
        'cake-app/package.json' => 'package.json',
        'cake-app/bun.lock' => 'bun.lock',
    ]);

    $lockfile = new JsLockfile($base);

    expect($lockfile->committedManager())->toBe(JsPackageManager::Bun);

    $alpine = inspectorCollection($lockfile->scan())->first(fn ($p): bool => $p->name() === 'alpinejs');
    expect($alpine->version())->toEqual('3.14.8');

    cleanup($base);
});

it('falls back to package.json when only bun.lockb is committed', function (): void {
    $tempDir = tempBase();
    file_put_contents($tempDir . 'bun.lockb', "\x00binary");
    file_put_contents($tempDir . 'package.json', json_encode([
        'dependencies' => ['vue' => '^3.4.0'],
    ]));

    $lockfile = new JsLockfile($tempDir);

    expect($lockfile->committedManager())->toBe(JsPackageManager::Bun);

    $vue = inspectorCollection($lockfile->scan())->first(fn ($p): bool => $p->name() === 'vue');
    expect($vue)->not->toBeNull();
    expect($vue->version())->toEqual('3.4.0');

    cleanup($tempDir);
});

it('reports the committed manager from lockfile presence', function (): void {
    $manager = (new JsLockfile(testDirectory('cake-app')))->committedManager();
    expect($manager)->toBe(JsPackageManager::Npm);
});

it('falls back to package.json when no lockfile is committed', function (): void {
    $tempDir = tempBase();

    file_put_contents($tempDir . 'package.json', json_encode([
        'dependencies' => ['vue' => '^3.4.0'],
        'devDependencies' => ['@inertiajs/react' => '^2.0.0'],
    ]));

    $packages = (new JsLockfile($tempDir))->scan();

    $vue = inspectorCollection($packages)->first(fn ($p): bool => $p->name() === 'vue');
    expect($vue)->not->toBeNull();
    expect($vue->version())->toEqual('3.4.0');
    expect($vue->isDirect())->toBeTrue();

    $inertia = inspectorCollection($packages)->first(fn ($p): bool => $p->name() === '@inertiajs/react');
    expect($inertia)->not->toBeNull();
    expect($inertia->isDev())->toBeTrue();

    cleanup($tempDir);
});

it('treats optional and peer dependencies as direct manifest packages', function (): void {
    $tempDir = tempBase();

    file_put_contents($tempDir . 'package.json', json_encode([
        'dependencies' => ['vue' => '^3.4.0'],
        'optionalDependencies' => ['fsevents' => '^2.3.0'],
        'peerDependencies' => ['react' => '^18.0.0'],
    ]));

    $packages = (new JsLockfile($tempDir))->scan();

    $fsevents = inspectorCollection($packages)->first(fn ($p): bool => $p->name() === 'fsevents');
    expect($fsevents)->not->toBeNull()
        ->and($fsevents->isDirect())->toBeTrue()
        ->and($fsevents->isDev())->toBeFalse();

    $react = inspectorCollection($packages)->first(fn ($p): bool => $p->name() === 'react');
    expect($react)->not->toBeNull()
        ->and($react->isDirect())->toBeTrue()
        ->and($react->isDev())->toBeFalse();

    cleanup($tempDir);
});

it('returns null committedManager when no lockfile present', function (): void {
    $tempDir = tempBase();

    $manager = (new JsLockfile($tempDir))->committedManager();
    expect($manager)->toBeNull();

    cleanup($tempDir);
});

it('falls back to package.json when the committed lockfile is unsupported', function (): void {
    $base = tempBase();

    file_put_contents($base . 'package-lock.json', json_encode([
        'lockfileVersion' => 1,
        'dependencies' => ['vue' => ['version' => '3.4.0']],
    ]));
    file_put_contents($base . 'package.json', json_encode(['dependencies' => ['vue' => '^3.4.0']]));

    $packages = (new JsLockfile($base))->scan();

    $vue = inspectorCollection($packages)->first(fn ($p): bool => $p->name() === 'vue');
    expect($vue)->not->toBeNull()
        ->and($vue->isDirect())->toBeTrue();

    cleanup($base);
});

it('does not substitute manifest data when the lockfile is valid but empty', function (): void {
    $base = tempBase();

    file_put_contents($base . 'package-lock.json', json_encode([
        'lockfileVersion' => 3,
        'packages' => [],
    ]));
    file_put_contents($base . 'package.json', json_encode(['dependencies' => ['vue' => '^3.4.0']]));

    expect((new JsLockfile($base))->scan())->toHaveCount(0);

    cleanup($base);
});
