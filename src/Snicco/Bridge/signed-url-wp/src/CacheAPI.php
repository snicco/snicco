<?php

declare(strict_types=1);

namespace Snicco\Bridge\SignedUrlWP;

use Snicco\Component\BetterWPAPI\BetterWPAPI;
use Snicco\Component\SignedUrl\Exception\UnavailableStorage;

use function is_int;
use function wp_cache_decr;

class CacheAPI extends BetterWPAPI
{
    /**
     * @throws UnavailableStorage
     */
    public function cacheDecr(string $key, int $offset, string $group = ''): int
    {
        $res = wp_cache_decr($key, $offset, $group);
        // @codeCoverageIgnoreStart
        if (! is_int($res)) {
            throw new UnavailableStorage('wp_cache_decr failed');
        }
        // @codeCoverageIgnoreEnd
        return $res;
    }
}
