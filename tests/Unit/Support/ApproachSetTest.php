<?php

declare(strict_types=1);

use Cake\Collection\Collection;
use Crustum\Inspector\ApproachResult;
use Crustum\Inspector\Enums\Approach;
use Crustum\Inspector\Support\ApproachSet;

it('reports membership and exposes results keyed by approach value', function (): void {
    $result = new ApproachResult(
        approach: Approach::EntityAccessibleWhitelist,
        confidence: 0.9,
        matched: 9,
        total: 10,
        paths: ['/src/Model/Entity/User.php'],
    );

    $set = new ApproachSet([$result]);

    expect($set->uses(Approach::EntityAccessibleWhitelist))->toBeTrue()
        ->and($set->uses(Approach::EntityAccessibleDefault))->toBeFalse()
        ->and($set->all())->toBeInstanceOf(Collection::class)
        ->and($set->result(Approach::EntityAccessibleWhitelist))->toBe($result);
});

it('retrieves a typed result per approach', function (): void {
    $result = new ApproachResult(
        approach: Approach::EntityAccessibleWhitelist,
        confidence: 0.9,
        matched: 9,
        total: 10,
        paths: ['/src/Model/Entity/User.php'],
    );

    $set = new ApproachSet([$result]);

    expect($set->result(Approach::EntityAccessibleWhitelist))->toBe($result)
        ->and($set->result(Approach::EntityAccessibleDefault))->toBeNull();
});

it('checks all-of membership with usesAll', function (): void {
    $whitelist = new ApproachResult(Approach::EntityAccessibleWhitelist, 1.0, 5, 5, []);
    $pascal = new ApproachResult(Approach::EnumCasePascal, 1.0, 1, 1, []);

    $set = new ApproachSet([$whitelist, $pascal]);

    expect($set->usesAll([Approach::EntityAccessibleWhitelist, Approach::EnumCasePascal]))->toBeTrue()
        ->and($set->usesAll([Approach::EntityAccessibleWhitelist, Approach::EnumCaseCamel]))->toBeFalse()
        ->and($set->usesAll([]))->toBeTrue();
});

it('serializes results to an array shape', function (): void {
    $result = new ApproachResult(
        approach: Approach::EnumCaseScreamingSnake,
        confidence: 1.0,
        matched: 5,
        total: 5,
        paths: ['/src/Enum/OrderStatus.php'],
    );

    expect($result->toArray())->toBe([
        'approach' => 'enum-case-screaming-snake',
        'confidence' => 1.0,
        'matched' => 5,
        'total' => 5,
        'paths' => ['/src/Enum/OrderStatus.php'],
    ]);
});

it('keeps distinct approaches side by side', function (): void {
    $set = new ApproachSet([
        new ApproachResult(Approach::EntityAccessibleWhitelist, 1.0, 5, 5, []),
        new ApproachResult(Approach::EnumCasePascal, 0.9, 9, 10, []),
    ]);

    expect($set->all())->toHaveCount(2)
        ->and($set->uses(Approach::EntityAccessibleWhitelist))->toBeTrue()
        ->and($set->uses(Approach::EnumCasePascal))->toBeTrue()
        ->and($set->result(Approach::EntityAccessibleWhitelist)?->confidence)->toBe(1.0)
        ->and($set->result(Approach::EnumCasePascal)?->confidence)->toBe(0.9);
});

it('keeps the first result when the same approach is reported twice', function (): void {
    $set = new ApproachSet([
        new ApproachResult(Approach::EntityAccessibleWhitelist, 1.0, 5, 5, []),
        new ApproachResult(Approach::EntityAccessibleWhitelist, 0.9, 9, 10, []),
    ]);

    expect($set->all())->toHaveCount(1)
        ->and($set->all()->first()?->confidence)->toBe(1.0)
        ->and($set->result(Approach::EntityAccessibleWhitelist)?->confidence)->toBe(1.0);
});
