<?php

declare(strict_types=1);

namespace Snicco\Bridge\SignedUrlWP;

use Snicco\Component\SignedUrl\Exception\BadIdentifier;
use Snicco\Component\SignedUrl\Exception\UnavailableStorage;
use Snicco\Component\SignedUrl\SignedUrl;
use Snicco\Component\SignedUrl\Storage\SignedUrlStorage;

use function time;

final class WPObjectCacheSignedUrlStorage implements SignedUrlStorage
{
    private string $cache_group;

    private CacheAPI $wp;

    public function __construct(string $cache_group, CacheAPI $wp = null)
    {
        $this->cache_group = $cache_group;
        $this->wp = $wp ?: new CacheAPI();
    }

    public function consume(string $identifier): void
    {
        $left_usages = $this->wp->cacheDecr($identifier, 1, $this->cache_group);

        if (0 > $left_usages) {
            throw BadIdentifier::for($identifier);
        }

        if (0 === $left_usages) {
            $removed = $this->wp->cacheDelete($identifier, $this->cache_group);
            if (false === $removed) {
                throw new UnavailableStorage(
                    "Signed url [{$identifier}] could not be deleted from the WP object cache."
                );
            }
        }
    }

    public function store(SignedUrl $signed_url): void
    {
        // A link should still be valid if the unix timestamp of the expiry is
        // equal to the current timestamp.
        // In order for that to work with the WP_Object_Cache
        // we have to increment by one.
        $lifetime = $signed_url->expiresAt() - time() + 1;

        $success = $this->wp->cacheSet(
            $signed_url->identifier(),
            $signed_url->maxUsage(),
            $this->cache_group,
            $lifetime
        );

        if (! $success) {
            throw new UnavailableStorage(
                "Singed url for protected path [{$signed_url->protects()}] could not be stored."
            );
        }
    }

    public function gc(): void
    {
        // Garbage collection is performed automatically
    }
}
