<?php
declare(strict_types=1);

namespace Crustum\Inspector\Enums;

/**
 * Application stack identifiers for CakePHP projects.
 */
enum Stack: string
{
    case Api = 'api';
    case Bake = 'bake';
    case Queue = 'queue';
    case DefaultView = 'default-view';
}
