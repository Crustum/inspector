<?php

declare(strict_types=1);

use Crustum\Inspector\Ecosystems\Ecosystem;
use Crustum\Inspector\PackageCollection;
use Crustum\Inspector\Scanners\CakePluginResolver;
use Crustum\Inspector\Scanners\ComposerLock;

function scaffoldCakePackage(
    string $base,
    string $package,
    string $namespace,
    string $plugin,
    ?string $cakeName,
    string $section = 'packages',
): string {
    [$vendor, $name] = explode('/', $package, 2);
    $dir = $base . 'vendor' . DIRECTORY_SEPARATOR . $vendor . DIRECTORY_SEPARATOR . $name . DIRECTORY_SEPARATOR;
    mkdir($dir . 'src', 0777, true);

    file_put_contents(
        $dir . 'composer.json',
        json_encode([
            'name' => $package,
            'type' => 'cakephp-plugin',
            'autoload' => ['psr-4' => [$namespace . '\\' => 'src/']],
        ]),
    );

    $nameLine = $cakeName === null ? '' : "    protected ?string \$name = '{$cakeName}';\n";
    file_put_contents(
        $dir . 'src' . DIRECTORY_SEPARATOR . $plugin . '.php',
        "<?php\ndeclare(strict_types=1);\n\nnamespace {$namespace};\n\nclass {$plugin}\n{\n{$nameLine}}\n",
    );

    $lockFile = $base . 'composer.lock';
    $lock = is_file($lockFile) ? json_decode((string)file_get_contents($lockFile), true) : [];
    if (!is_array($lock)) {
        $lock = [];
    }

    $lock[$section][] = ['name' => $package, 'version' => 'v1.2.0', 'type' => 'cakephp-plugin'];
    file_put_contents($lockFile, json_encode($lock));

    return $dir;
}

it('resolves the plugin class through the psr-4 map and reads the explicit cake name', function (): void {
    $base = tempBase();
    scaffoldCakePackage($base, 'acme/blog-one', 'Acme\\BlogOne', 'BlogOnePlugin', 'BlogOne');

    $details = (new ComposerLock($base))->scan()->byType('cakephp-plugin')->all();
    $resolved = (new CakePluginResolver())->resolve($details);

    expect($resolved)->toHaveCount(1)
        ->and($resolved[0]->package())->toBe('acme/blog-one')
        ->and($resolved[0]->pluginClass())->toBe('Acme\\BlogOne\\BlogOnePlugin')
        ->and($resolved[0]->name())->toBe('BlogOne')
        ->and($resolved[0]->version())->toBe('1.2.0');

    cleanup($base);
});

it('falls back to the namespace path when no explicit cake name is set', function (): void {
    $base = tempBase();
    scaffoldCakePackage($base, 'acme/blog-two', 'Acme\\BlogTwo', 'BlogTwoPlugin', null);

    $details = (new ComposerLock($base))->scan()->byType('cakephp-plugin')->all();
    $resolved = (new CakePluginResolver())->resolve($details);

    expect($resolved)->toHaveCount(1)
        ->and($resolved[0]->name())->toBe('Acme/BlogTwo');

    cleanup($base);
});

it('resolves the class from the composer-name convention', function (): void {
    $base = tempBase();
    $dir = $base . 'vendor' . DIRECTORY_SEPARATOR . 'acme' . DIRECTORY_SEPARATOR . 'shop-three' . DIRECTORY_SEPARATOR;
    mkdir($dir . 'src', 0777, true);
    file_put_contents(
        $dir . 'composer.json',
        json_encode(['name' => 'acme/shop-three', 'type' => 'cakephp-plugin']),
    );
    file_put_contents(
        $dir . 'src' . DIRECTORY_SEPARATOR . 'ShopThreePlugin.php',
        "<?php\ndeclare(strict_types=1);\n\nnamespace Acme\\ShopThree;\n\nclass ShopThreePlugin\n{\n    protected ?string \$name = 'ShopThree';\n}\n",
    );
    file_put_contents($base . 'composer.lock', json_encode([
        'packages' => [['name' => 'acme/shop-three', 'version' => 'v1.0.0', 'type' => 'cakephp-plugin']],
        'packages-dev' => [],
    ]));

    $details = (new ComposerLock($base))->scan()->byType('cakephp-plugin')->all();
    $resolved = (new CakePluginResolver())->resolve($details);

    expect($resolved)->toHaveCount(1)
        ->and($resolved[0]->pluginClass())->toBe('Acme\\ShopThree\\ShopThreePlugin');

    cleanup($base);
});

it('skips packages without a resolvable plugin class', function (): void {
    $base = tempBase();
    file_put_contents($base . 'composer.lock', json_encode([
        'packages' => [['name' => 'acme/ghost-four', 'version' => 'v1.0.0', 'type' => 'cakephp-plugin']],
        'packages-dev' => [],
    ]));

    $details = (new ComposerLock($base))->scan()->byType('cakephp-plugin')->all();
    $resolved = (new CakePluginResolver())->resolve($details);

    expect($resolved)->toBe([]);

    cleanup($base);
});

it('ignores non-cakephp-plugin packages', function (): void {
    $base = tempBase();
    file_put_contents($base . 'composer.lock', json_encode([
        'packages' => [['name' => 'acme/lib-five', 'version' => 'v1.0.0', 'type' => 'library']],
        'packages-dev' => [],
    ]));

    $details = (new ComposerLock($base))->scan()->byType('cakephp-plugin')->all();
    $resolved = (new CakePluginResolver())->resolve(
        (new ComposerLock($base))->scan()->all(),
    );

    expect($details)->toBe([])
        ->and($resolved)->toBe([]);

    cleanup($base);
});

it('resolves plugins from explicit extra paths', function (): void {
    $base = tempBase();
    $dir = $base . 'local' . DIRECTORY_SEPARATOR . 'extra-six' . DIRECTORY_SEPARATOR;
    mkdir($dir . 'src', 0777, true);
    file_put_contents(
        $dir . 'composer.json',
        json_encode([
            'name' => 'acme/extra-six',
            'type' => 'cakephp-plugin',
            'autoload' => ['psr-4' => ['Acme\\ExtraSix\\' => 'src/']],
        ]),
    );
    file_put_contents(
        $dir . 'src' . DIRECTORY_SEPARATOR . 'ExtraSixPlugin.php',
        "<?php\ndeclare(strict_types=1);\n\nnamespace Acme\\ExtraSix;\n\nclass ExtraSixPlugin\n{\n    protected ?string \$name = 'ExtraSix';\n}\n",
    );

    $resolved = (new CakePluginResolver())->resolve([], [$dir]);

    expect($resolved)->toHaveCount(1)
        ->and($resolved[0]->package())->toBe('acme/extra-six')
        ->and($resolved[0]->name())->toBe('ExtraSix')
        ->and($resolved[0]->isDirect())->toBeTrue();

    cleanup($base);
});

it('reports each plugin class only once', function (): void {
    $base = tempBase();
    $dir = scaffoldCakePackage($base, 'acme/blog-seven', 'Acme\\BlogSeven', 'BlogSevenPlugin', 'BlogSeven');

    $details = (new ComposerLock($base))->scan()->byType('cakephp-plugin')->all();
    $resolved = (new CakePluginResolver())->resolve($details, [$dir]);

    expect($resolved)->toHaveCount(1);

    cleanup($base);
});

it('exposes cake plugin details through the ecosystem', function (): void {
    $php = new Ecosystem(new PackageCollection());

    expect($php->cakePluginDetails())->toBe([])
        ->and($php->cakePluginDetails(['/no/such/dir']))->toBe([]);
});
