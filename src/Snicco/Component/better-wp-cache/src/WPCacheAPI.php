<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPCache;

use InvalidArgumentException;
use Snicco\Component\BetterWPAPI\BetterWPAPI;

use function array_key_exists;
use function is_string;
use function wp_cache_flush;
use function wp_cache_get_multiple;

/**
 * @psalm-internal Snicco\Component\BetterWPCache
 */
class WPCacheAPI extends BetterWPAPI
{
    /**
     * @param string[] $keys
     *
     * @return array<string,mixed>
     */
    public function cacheGetMultiple(array $keys, string $group = '', bool $force_reload = false): array
    {
        $cached_values = [];

        /**
         * @var mixed $value
         */
        foreach (wp_cache_get_multiple($keys, $group, $force_reload) as $key => $value) {
            if (! is_string($key)) {
                // Object cache returned bad values. The returned array should be keyed by the provided keys.
                if (! array_key_exists((string) $key, $keys)) {
                    // @codeCoverageIgnoreStart
                    throw new InvalidArgumentException(
                        'wp_cache_get_multiple must return an array of type <string,mixed>'
                    );
                    // @codeCoverageIgnoreEnd
                }

                // We need to cast keys like (string) "1" back to a string to fulfill the PSR cache API.
                $key = (string) $key;
            }

            /**
             * @psalm-suppress MixedAssignment
             */
            $cached_values[$key] = $value;
        }

        return $cached_values;
    }

    public function cacheFlush(): bool
    {
        return wp_cache_flush();
    }
}
