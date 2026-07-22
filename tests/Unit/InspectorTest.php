<?php

declare(strict_types=1);

use Crustum\Inspector\Enums\Frontend;
use Crustum\Inspector\Enums\JsPackageManager;
use Crustum\Inspector\Enums\Stack;
use Crustum\Inspector\ProjectScan;

it('scans the cake-app fixture end to end', function (): void {
    $project = ProjectScan::scan(testDirectory('cake-app'));

    expect($project->php()->uses('pestphp/pest'))->toBeTrue();
    expect($project->php()->uses('cakephp/cakephp'))->toBeTrue();
    expect($project->php()->uses('crustum/notification'))->toBeTrue();
    expect($project->php()->uses('phpstan/phpstan'))->toBeTrue();

    expect($project->js()->uses('tailwindcss'))->toBeTrue();
    expect($project->js()->uses('@laravel/echo-react'))->toBeTrue();

    expect($project->stacks()->uses(Stack::Bake))->toBeTrue();
    expect($project->stacks()->uses(Stack::DefaultView))->toBeFalse();
    expect($project->frontends()->uses(Frontend::Vue))->toBeFalse();

    expect($project->js()->packageManager())->toBe(JsPackageManager::Npm);
    expect($project->js()->usesPackageManager(JsPackageManager::Npm))->toBeTrue();
    expect($project->js()->usesPackageManager('npm'))->toBeTrue();
    expect($project->js()->usesPackageManager(JsPackageManager::Pnpm))->toBeFalse();
    expect($project->js()->usesPackageManager('not-a-package-manager'))->toBeFalse();
});

it('renders json without error', function (): void {
    $project = ProjectScan::scan(testDirectory('cake-app'));
    $payload = json_decode($project->json(), true);

    expect($payload)->toBeArray();
    expect($payload)->toHaveKey('php');
    expect($payload)->toHaveKey('js');
    expect($payload)->toHaveKey('stacks');
});
