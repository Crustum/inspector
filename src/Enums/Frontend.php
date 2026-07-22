<?php
declare(strict_types=1);

namespace Crustum\Inspector\Enums;

/**
 * Frontend framework identifiers detected from JS dependencies.
 */
enum Frontend: string
{
    case Vue = 'vue';
    case React = 'react';
    case Svelte = 'svelte';
}
