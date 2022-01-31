<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPHooks;

use WP_Hook;

use function current_filter;

final class ScopableWP extends \Snicco\Component\ScopableWP\ScopableWP
{

    /**
     * @return string|false
     */
    public function currentFilter()
    {
        return current_filter();
    }

    public function getHook(string $hook_name): ?WP_Hook
    {
        return $GLOBALS['wp_filter'][$hook_name] ?? null;
    }

}