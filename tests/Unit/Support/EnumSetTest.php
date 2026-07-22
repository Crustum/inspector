<?php

declare(strict_types=1);

use Crustum\Inspector\Enums\Stack;
use Crustum\Inspector\Support\EnumSet;

it('checks membership for a single case', function (): void {
    $set = new EnumSet([Stack::Api, Stack::Bake]);

    expect($set->uses(Stack::Api))->toBeTrue();
    expect($set->uses(Stack::Queue))->toBeFalse();
});

it('checks any-of membership for an array of cases', function (): void {
    $set = new EnumSet([Stack::Bake]);

    expect($set->uses([Stack::DefaultView, Stack::Bake]))->toBeTrue();
    expect($set->uses([Stack::Queue, Stack::Api]))->toBeFalse();
});

it('checks all-of membership with usesAll', function (): void {
    $set = new EnumSet([Stack::Api, Stack::Bake]);

    expect($set->usesAll([Stack::Api, Stack::Bake]))->toBeTrue();
    expect($set->usesAll([Stack::Api, Stack::Queue]))->toBeFalse();
    expect($set->usesAll([]))->toBeTrue();
});

it('exposes the raw cases and their values', function (): void {
    $set = new EnumSet([Stack::Api, Stack::Bake]);

    expect($set->all())->toBe([Stack::Api, Stack::Bake]);
    expect($set->values())->toBe(['api', 'bake']);
});
