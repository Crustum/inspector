<?php

declare(strict_types=1);

use Crustum\Inspector\Enums\Approach;
use Crustum\Inspector\ProjectScan;

it('exposes approaches from a full project scan', function (): void {
    $project = ProjectScan::scan(testDirectory('approaches/accessible-whitelist-entities-app'));

    expect($project->approaches()->uses(Approach::EntityAccessibleWhitelist))->toBeTrue()
        ->and($project->approaches()->uses(Approach::EntityAccessibleDefault))->toBeFalse();
});

it('keeps the array payload cheap by omitting approaches', function (): void {
    $payload = ProjectScan::scan(testDirectory('approaches/accessible-whitelist-entities-app'))->toArray();

    expect($payload)->not->toHaveKey('approaches');
});

it('strips computed approaches from the serialized payload', function (): void {
    $project = ProjectScan::scan(testDirectory('approaches/accessible-whitelist-entities-app'));

    $project->approaches();

    expect($project->__serialize())
        ->not->toHaveKey('approaches')
        ->and(serialize($project))->not->toContain('ApproachSet');
});

it('recomputes approaches lazily after a serialize round-trip', function (): void {
    $project = ProjectScan::scan(testDirectory('approaches/accessible-whitelist-entities-app'));

    $project->approaches();

    $restored = unserialize(serialize($project));

    expect($restored)->toBeInstanceOf(ProjectScan::class)
        ->and($restored)->not->toBe($project)
        ->and($restored->approaches()->uses(Approach::EntityAccessibleWhitelist))->toBeTrue()
        ->and($restored->approaches()->uses(Approach::EntityAccessibleDefault))->toBeFalse();
});
