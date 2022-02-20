<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPHooks;

use RuntimeException;
use Snicco\Component\BetterWPAPI\BetterWPAPI;
use WP_Hook;

use function current_filter;
use function gettype;
use function is_string;
use function sprintf;

/**
 * @psalm-internal Snicco
 */
final class WPHookAPI extends BetterWPAPI
{

    /**
     * @psalm-internal Snicco\Component\BetterWPHooks
     *
     * @interal
     */
    public function currentFilter(): ?string
    {
        /** @var string|false $filter */
        $filter = current_filter();
        if (is_string($filter)) {
            return $filter;
        }
        return null;
    }

    /**
     * @psalm-internal Snicco\Component\BetterWPHooks
     *
     * @interal
     */
    public function getHook(string $hook_name): ?WP_Hook
    {
        $hook = $GLOBALS['wp_filter'][$hook_name] ?? null;
        if (null === $hook) {
            return null;
        }
        if (!$hook instanceof WP_Hook) {
            throw new RuntimeException(
                sprintf(
                    "The registered hook [$hook_name] has to be an instance of WP_Hook.\nGot: [%s].",
                    gettype($hook)
                )
            );
        }
        return $hook;
    }

}