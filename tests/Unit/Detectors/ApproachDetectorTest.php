<?php

declare(strict_types=1);

use Crustum\Inspector\Detectors\ApproachDetector;
use Crustum\Inspector\Enums\Approach;
use Crustum\Inspector\Support\ApproachSet;

function approachesFixturePath(string $app): string
{
    return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'approaches' . DIRECTORY_SEPARATOR . $app;
}

function detectApproaches(string $app): ApproachSet
{
    return new ApproachSet(ApproachDetector::detect(approachesFixturePath($app)));
}

function writeEntity(string $base, string $name, string $accessible): void
{
    touchFile($base . 'src/Model/Entity/' . $name . '.php');
    file_put_contents(
        $base . 'src/Model/Entity/' . $name . '.php',
        "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\Model\\Entity;\n\nuse Cake\\ORM\\Entity;\n\nclass {$name} extends Entity\n{\n    protected array \$_accessible = {$accessible};\n}\n",
    );
}

function writeSource(string $base, string $relative, string $body): void
{
    touchFile($base . $relative);
    file_put_contents($base . $relative, "<?php\n\ndeclare(strict_types=1);\n\n" . $body . "\n");
}

it('detects entity accessible whitelist as the dominant style', function (): void {
    $approaches = detectApproaches('accessible-whitelist-entities-app');

    expect($approaches->uses(Approach::EntityAccessibleWhitelist))->toBeTrue()
        ->and($approaches->uses(Approach::EntityAccessibleDefault))->toBeFalse();

    $result = $approaches->result(Approach::EntityAccessibleWhitelist);
    expect($result)->not->toBeNull()
        ->and($result->matched)->toBe(6)
        ->and($result->total)->toBe(6)
        ->and($result->confidence)->toBe(1.0)
        ->and($result->paths)->toHaveCount(6)
        ->and($result->paths[0])->toContain('Model' . DIRECTORY_SEPARATOR . 'Entity');
});

it('detects accessible default as the dominant entity style', function (): void {
    $approaches = detectApproaches('accessible-default-entities-app');

    expect($approaches->uses(Approach::EntityAccessibleDefault))->toBeTrue()
        ->and($approaches->uses(Approach::EntityAccessibleWhitelist))->toBeFalse();

    $result = $approaches->result(Approach::EntityAccessibleDefault);
    expect($result)->not->toBeNull()
        ->and($result->matched)->toBe(5)
        ->and($result->total)->toBe(5);
});

it('stays silent when entities are split between whitelist and default', function (): void {
    $approaches = detectApproaches('mixed-entities-app');

    expect($approaches->uses(Approach::EntityAccessibleWhitelist))->toBeFalse()
        ->and($approaches->uses(Approach::EntityAccessibleDefault))->toBeFalse();
});

it('detects screaming snake enum case naming with one vote per case', function (): void {
    $approaches = detectApproaches('screaming-enums-app');

    expect($approaches->uses(Approach::EnumCaseScreamingSnake))->toBeTrue();

    $result = $approaches->result(Approach::EnumCaseScreamingSnake);
    expect($result)->not->toBeNull()
        ->and($result->matched)->toBe(5)
        ->and($result->total)->toBe(5)
        ->and($result->paths)->toHaveCount(2);
});

it('does not count switch case labels as enum cases', function (): void {
    $approaches = detectApproaches('enum-switch-app');

    expect($approaches->uses(Approach::EnumCaseScreamingSnake))->toBeTrue();

    $result = $approaches->result(Approach::EnumCaseScreamingSnake);
    expect($result)->not->toBeNull()
        ->and($result->matched)->toBe(5)
        ->and($result->total)->toBe(5);
});

it('detects pascal enum case naming', function (): void {
    $approaches = detectApproaches('pascal-enums-app');

    expect($approaches->uses(Approach::EnumCasePascal))->toBeTrue()
        ->and($approaches->uses(Approach::EnumCaseScreamingSnake))->toBeFalse();

    $result = $approaches->result(Approach::EnumCasePascal);
    expect($result)->not->toBeNull()
        ->and($result->matched)->toBe(5)
        ->and($result->total)->toBe(5);
});

it('detects camel enum case naming', function (): void {
    $approaches = detectApproaches('camel-enums-app');

    expect($approaches->uses(Approach::EnumCaseCamel))->toBeTrue();

    $result = $approaches->result(Approach::EnumCaseCamel);
    expect($result)->not->toBeNull()
        ->and($result->matched)->toBe(5)
        ->and($result->total)->toBe(5);
});

it('counts enum cases declared after a method with string interpolation', function (): void {
    $approaches = detectApproaches('interpolated-enums-app');

    expect($approaches->uses(Approach::EnumCaseScreamingSnake))->toBeTrue();

    $result = $approaches->result(Approach::EnumCaseScreamingSnake);
    expect($result)->not->toBeNull()
        ->and($result->matched)->toBe(5)
        ->and($result->total)->toBe(5);
});

it('samples Model/Entity directories anywhere beneath a PSR-4 root', function (): void {
    $approaches = detectApproaches('nested-entities-app');

    expect($approaches->uses(Approach::EntityAccessibleWhitelist))->toBeTrue();

    $result = $approaches->result(Approach::EntityAccessibleWhitelist);
    expect($result)->not->toBeNull()
        ->and($result->matched)->toBe(5)
        ->and($result->paths[0])->toContain('src' . DIRECTORY_SEPARATOR . 'Domain' . DIRECTORY_SEPARATOR . 'Orders' . DIRECTORY_SEPARATOR . 'Model' . DIRECTORY_SEPARATOR . 'Entity');
});

it('stays silent when there are too few votes', function (): void {
    expect(detectApproaches('min-sample-app')->all()->toArray())->toBe([]);
});

it('reports a convention once the minimum sample of three is reached', function (): void {
    $base = tempBase();

    foreach (['Alpha', 'Bravo', 'Charlie'] as $name) {
        writeEntity($base, $name, "['name' => true]");
    }

    $approaches = new ApproachSet(ApproachDetector::detect($base));

    expect($approaches->uses(Approach::EntityAccessibleWhitelist))->toBeTrue();

    cleanup($base);
});

it('rejects a 3/4 majority as insufficient evidence', function (): void {
    $base = tempBase();

    foreach (['Alpha', 'Bravo', 'Charlie'] as $name) {
        writeEntity($base, $name, "['name' => true]");
    }

    writeEntity($base, 'Hotel', "['*' => true]");

    $approaches = new ApproachSet(ApproachDetector::detect($base));

    expect($approaches->uses(Approach::EntityAccessibleWhitelist))->toBeFalse()
        ->and($approaches->uses(Approach::EntityAccessibleDefault))->toBeFalse();

    cleanup($base);
});

it('accepts a 4/5 majority at the confidence floor', function (): void {
    $base = tempBase();

    foreach (['Alpha', 'Bravo', 'Charlie', 'Delta'] as $name) {
        writeEntity($base, $name, "['name' => true]");
    }

    writeEntity($base, 'Hotel', "['*' => true]");

    $approaches = new ApproachSet(ApproachDetector::detect($base));

    expect($approaches->uses(Approach::EntityAccessibleWhitelist))->toBeTrue()
        ->and($approaches->uses(Approach::EntityAccessibleDefault))->toBeFalse();

    cleanup($base);
});

it('accepts a 90/100 majority', function (): void {
    $base = tempBase();

    for ($i = 1; $i <= 90; $i++) {
        writeEntity($base, 'Whitelist' . $i, "['name' => true]");
    }

    for ($i = 1; $i <= 10; $i++) {
        writeEntity($base, 'Default' . $i, "['*' => true]");
    }

    $approaches = new ApproachSet(ApproachDetector::detect($base));

    expect($approaches->uses(Approach::EntityAccessibleWhitelist))->toBeTrue();

    $result = $approaches->result(Approach::EntityAccessibleWhitelist);
    expect($result)->not->toBeNull()
        ->and($result->matched)->toBe(90)
        ->and($result->total)->toBe(100)
        ->and($result->confidence)->toBe(0.9)
        ->and($result->paths)->toHaveCount(100);

    cleanup($base);
});

it('classifies a wildcard accessible entity as default even when explicit fields are present', function (): void {
    $base = tempBase();

    foreach (['Alpha', 'Bravo', 'Charlie', 'Delta', 'Hotel'] as $name) {
        writeEntity($base, $name, "['name' => true]");
    }

    touchFile($base . 'src/Model/Entity/Both.php');
    file_put_contents(
        $base . 'src/Model/Entity/Both.php',
        "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\Model\\Entity;\n\nuse Cake\\ORM\\Entity;\n\nclass Both extends Entity\n{\n    protected array \$_accessible = [\n        'name' => true,\n        '*' => false,\n    ];\n}\n",
    );

    $approaches = new ApproachSet(ApproachDetector::detect($base));

    $result = $approaches->result(Approach::EntityAccessibleWhitelist);
    expect($result)->not->toBeNull()
        ->and($result->matched)->toBe(5)
        ->and($result->total)->toBe(6)
        ->and($result->paths)->toHaveCount(6);

    cleanup($base);
});

it('detects typed property declarations of accessible fields', function (): void {
    $base = tempBase();

    foreach (['Alpha', 'Bravo', 'Charlie', 'Delta', 'Hotel'] as $name) {
        touchFile($base . 'src/Model/Entity/' . $name . '.php');
        file_put_contents(
            $base . 'src/Model/Entity/' . $name . '.php',
            "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\Model\\Entity;\n\nuse Cake\\ORM\\Entity;\n\nclass {$name} extends Entity\n{\n    protected array \$_accessible = ['name' => true];\n}\n",
        );
    }

    $approaches = new ApproachSet(ApproachDetector::detect($base));

    expect($approaches->uses(Approach::EntityAccessibleWhitelist))->toBeTrue()
        ->and($approaches->uses(Approach::EntityAccessibleDefault))->toBeFalse();

    $result = $approaches->result(Approach::EntityAccessibleWhitelist);
    expect($result)->not->toBeNull()
        ->and($result->matched)->toBe(5)
        ->and($result->total)->toBe(5);

    cleanup($base);
});

it('detects accessible declared with multiple type and modifier tokens', function (): void {
    $base = tempBase();

    foreach (['Alpha', 'Bravo', 'Charlie', 'Delta', 'Hotel'] as $name) {
        touchFile($base . 'src/Model/Entity/' . $name . '.php');
        file_put_contents(
            $base . 'src/Model/Entity/' . $name . '.php',
            "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\Model\\Entity;\n\nuse Cake\\ORM\\Entity;\n\nclass {$name} extends Entity\n{\n    protected readonly array \$_accessible = ['name' => true];\n}\n",
        );
    }

    $approaches = new ApproachSet(ApproachDetector::detect($base));

    expect($approaches->uses(Approach::EntityAccessibleWhitelist))->toBeTrue()
        ->and($approaches->uses(Approach::EntityAccessibleDefault))->toBeFalse();

    $result = $approaches->result(Approach::EntityAccessibleWhitelist);
    expect($result)->not->toBeNull()
        ->and($result->matched)->toBe(5)
        ->and($result->total)->toBe(5);

    cleanup($base);
});

it('never lets vendor or node_modules code vote', function (): void {
    $base = tempBase();

    file_put_contents($base . 'composer.json', json_encode([
        'autoload' => ['psr-4' => ['App\\' => 'src/']],
    ]));

    foreach (['vendor/acme/pkg/src', 'node_modules/pkg'] as $dir) {
        for ($i = 1; $i <= 5; $i++) {
            touchFile($base . $dir . '/Model/Entity/Dep' . $i . '.php');
            file_put_contents(
                $base . $dir . '/Model/Entity/Dep' . $i . '.php',
                "<?php\n\ndeclare(strict_types=1);\n\nnamespace Dep;\n\nuse Cake\\ORM\\Entity;\n\nclass Dep{$i} extends Entity\n{\n    protected array \$_accessible = ['*' => true];\n}\n",
            );
        }
    }

    $approaches = new ApproachSet(ApproachDetector::detect($base));

    expect($approaches->all()->toArray())->toBe([]);

    cleanup($base);
});

it('accepts an array of approaches with any-of semantics', function (): void {
    $approaches = detectApproaches('accessible-whitelist-entities-app');

    expect($approaches->uses([Approach::EntityAccessibleWhitelist, Approach::EntityAccessibleDefault]))->toBeTrue()
        ->and($approaches->uses([Approach::EntityAccessibleDefault, Approach::EnumCaseCamel]))->toBeFalse();
});

it('dedupes files reachable through overlapping source roots', function (): void {
    $base = tempBase();

    file_put_contents($base . 'composer.json', json_encode([
        'autoload' => ['psr-4' => ['App\\' => 'src/', 'App\\Model\\Entity\\' => 'src/Model/Entity/']],
    ]));

    foreach (['Alpha', 'Bravo', 'Charlie', 'Delta', 'Hotel'] as $name) {
        writeEntity($base, $name, "['name' => true]");
    }

    $approaches = new ApproachSet(ApproachDetector::detect($base));

    $result = $approaches->result(Approach::EntityAccessibleWhitelist);
    expect($result)->not->toBeNull()
        ->and($result->total)->toBe(5);

    cleanup($base);
});
