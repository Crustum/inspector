<?php
declare(strict_types=1);

namespace Crustum\Inspector\Detectors;

use Crustum\Inspector\Ecosystems\Ecosystem;
use Crustum\Inspector\Enums\Stack;

/**
 * Detects CakePHP application stacks from composer dependencies.
 */
class StackDetector
{
    /**
     * @var list<string>
     */
    private const API_PACKAGES = [
        'friendsofcake/crud',
        'cakedc/cakephp-api',
    ];

    /**
     * Detect stacks in use. Multiple stacks may apply.
     *
     * @param \Crustum\Inspector\Ecosystems\Ecosystem $php PHP ecosystem
     * @return list<\Crustum\Inspector\Enums\Stack>
     */
    public static function detect(Ecosystem $php): array
    {
        $stacks = [];

        if ($php->usesDirect(self::API_PACKAGES)) {
            $stacks[] = Stack::Api;
        }

        if ($php->uses('cakephp/bake')) {
            $stacks[] = Stack::Bake;
        }

        if ($php->usesDirect('cakephp/queue')) {
            $stacks[] = Stack::Queue;
        }

        if ($stacks === [] && $php->uses('cakephp/cakephp')) {
            $stacks[] = Stack::DefaultView;
        }

        return $stacks;
    }
}
