<?php
declare(strict_types=1);

namespace Crustum\Inspector\Enums;

/**
 * Source-code convention identifiers detected in PHP files.
 */
enum Approach: string
{
    case EnumCaseScreamingSnake = 'enum-case-screaming-snake';
    case EnumCasePascal = 'enum-case-pascal';
    case EnumCaseCamel = 'enum-case-camel';
    case EntityAccessibleWhitelist = 'entity-accessible-whitelist';
    case EntityAccessibleDefault = 'entity-accessible-default';
}
