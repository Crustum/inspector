<?php
declare(strict_types=1);

use Boundwize\StructArmed\Architecture;

return Architecture::define()
    ->layerPattern('Foundation', [
        '/^Crustum\\\\Inspector\\\\(Enums|Support)(\\\\.*)?$/',
        '/^Crustum\\\\Inspector\\\\(Package|PackageCollection|ApproachResult)$/',
    ])
    ->layerPattern('Ecosystems', '/^Crustum\\\\Inspector\\\\Ecosystems(\\\\.*)?$/')
    ->layerPattern('Detectors', '/^Crustum\\\\Inspector\\\\Detectors(\\\\.*)?$/')
    ->layerPattern('Scanners', '/^Crustum\\\\Inspector\\\\Scanners(\\\\.*)?$/')
    ->layerPattern('Project', [
        '/^Crustum\\\\Inspector\\\\(ProjectManager|ProjectScan)$/',
    ])
    ->layerPattern('Command', '/^Crustum\\\\Inspector\\\\Command\\\\.*$/')
    ->layerPattern('Plugin', '/^Crustum\\\\Inspector\\\\InspectorPlugin$/')
    ->ruleset([
        'Foundation' => [],
        'Ecosystems' => ['Foundation'],
        'Detectors' => ['Ecosystems', 'Foundation'],
        'Scanners' => ['Foundation'],
        'Project' => ['Detectors', 'Scanners', 'Ecosystems', 'Foundation'],
        'Command' => ['Project', 'Foundation'],
        'Plugin' => ['+Command'],
    ]);
