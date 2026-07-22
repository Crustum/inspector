<?php
declare(strict_types=1);

namespace Crustum\Inspector\Detectors;

use Crustum\Inspector\Ecosystems\JsEcosystem;
use Crustum\Inspector\Enums\Frontend;

/**
 * Detects frontend frameworks from JS direct dependencies.
 */
class FrontendDetector
{
    /**
     * @var array<string, list<string>>
     */
    private const MARKERS = [
        'vue' => ['vue', '@vitejs/plugin-vue', '@inertiajs/vue3'],
        'react' => ['react', 'react-dom', '@vitejs/plugin-react', '@inertiajs/react'],
        'svelte' => ['svelte', '@sveltejs/kit', '@sveltejs/vite-plugin-svelte', '@inertiajs/svelte'],
    ];

    /**
     * Detect frontends in use.
     *
     * @param \Crustum\Inspector\Ecosystems\JsEcosystem $js JS ecosystem
     * @return list<\Crustum\Inspector\Enums\Frontend>
     */
    public static function detect(JsEcosystem $js): array
    {
        $found = [];

        foreach (self::MARKERS as $value => $markers) {
            if ($js->usesDirect($markers)) {
                $found[] = Frontend::from($value);
            }
        }

        return $found;
    }
}
