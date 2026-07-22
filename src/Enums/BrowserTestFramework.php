<?php
declare(strict_types=1);

namespace Crustum\Inspector\Enums;

/**
 * Browser test framework identifiers.
 */
enum BrowserTestFramework: string
{
    case Playwright = 'playwright';
    case Cypress = 'cypress';
    case Codeception = 'codeception';
}
