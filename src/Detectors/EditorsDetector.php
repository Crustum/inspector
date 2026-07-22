<?php
declare(strict_types=1);

namespace Crustum\Inspector\Detectors;

use Crustum\Inspector\Enums\Editor;

/**
 * Detects editors from project filesystem markers.
 *
 * @extends \Crustum\Inspector\Detectors\MarkerDetector<\Crustum\Inspector\Enums\Editor>
 */
class EditorsDetector extends MarkerDetector
{
    protected const ENUM = Editor::class;

    /**
     * @var array<string, list<string>>
     */
    private const PROJECT_MARKERS = [
        Editor::PhpStorm->value => ['.idea'],
        Editor::VsCode->value => ['.vscode'],
        Editor::Zed->value => ['.zed'],
        Editor::SublimeText->value => ['*.sublime-project', '*.sublime-workspace'],
    ];

    /**
     * @inheritDoc
     */
    protected static function projectMarkers(): array
    {
        return self::PROJECT_MARKERS;
    }
}
