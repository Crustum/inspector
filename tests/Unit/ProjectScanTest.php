<?php

declare(strict_types=1);

use Crustum\Inspector\ProjectScan;

it('finishes an explicit base path with a directory separator', function (): void {
    expect(ProjectScan::normalizeBasePath('/tmp/project'))
        ->toBe('/tmp/project' . DIRECTORY_SEPARATOR);
});

it('falls back to the working directory when no base path is given', function (): void {
    $cwd = getcwd() ?: '.';

    expect(ProjectScan::normalizeBasePath(null))
        ->toBe(rtrim($cwd, DIRECTORY_SEPARATOR . '/') . DIRECTORY_SEPARATOR);
});
