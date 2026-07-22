<?php
declare(strict_types=1);

namespace Crustum\Inspector\Enums;

/**
 * Source of a detected package dependency.
 */
enum PackageSource: string
{
    case Composer = 'composer';
    case Npm = 'npm';
}
