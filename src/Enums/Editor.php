<?php
declare(strict_types=1);

namespace Crustum\Inspector\Enums;

/**
 * Editor identifiers detected from project markers.
 */
enum Editor: string
{
    case PhpStorm = 'phpstorm';
    case VsCode = 'vscode';
    case Zed = 'zed';
    case SublimeText = 'sublime-text';
}
