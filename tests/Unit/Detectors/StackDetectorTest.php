<?php

declare(strict_types=1);

use Crustum\Inspector\Detectors\StackDetector;
use Crustum\Inspector\Enums\Stack;

it('detects api stack from CRUD', function (): void {
    $stacks = StackDetector::detect(phpEcosystem(['friendsofcake/crud']));

    expect($stacks)->toContain(Stack::Api);
});

it('detects api stack from CakeDC API', function (): void {
    $stacks = StackDetector::detect(phpEcosystem(['cakedc/cakephp-api']));

    expect($stacks)->toContain(Stack::Api);
});

it('detects bake stack', function (): void {
    $stacks = StackDetector::detect(phpEcosystem(['cakephp/bake']));

    expect($stacks)->toContain(Stack::Bake);
});

it('detects queue stack', function (): void {
    $stacks = StackDetector::detect(phpEcosystem(['cakephp/queue']));

    expect($stacks)->toContain(Stack::Queue);
});

it('falls back to default-view when cakephp is present and no other stack matches', function (): void {
    $stacks = StackDetector::detect(phpEcosystem(['cakephp/cakephp']));

    expect($stacks)->toContain(Stack::DefaultView);
});

it('does not add default-view when another stack is detected', function (): void {
    $stacks = StackDetector::detect(phpEcosystem(['cakephp/bake', 'cakephp/cakephp']));

    expect($stacks)->toContain(Stack::Bake);
    expect($stacks)->not->toContain(Stack::DefaultView);
});

it('detects no stack for empty projects', function (): void {
    $stacks = StackDetector::detect(phpEcosystem([]));

    expect($stacks)->toBe([]);
});
