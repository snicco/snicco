<?php

declare(strict_types=1);

namespace Snicco\Bridge\SignedUrlPsr16;

use Psr\SimpleCache\CacheInterface;
use Snicco\Component\SignedUrl\Exception\BadIdentifier;
use Snicco\Component\SignedUrl\Exception\UnavailableStorage;
use Snicco\Component\SignedUrl\SignedUrl;
use Snicco\Component\SignedUrl\Storage\SignedUrlStorage;

use function is_array;
use function time;

final class Psr16Storage implements SignedUrlStorage
{

    private CacheInterface $cache;

    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    public function consume(string $identifier): void
    {
        $key = $this->buildCacheKey($identifier);

        $data = $this->cache->get($key);

        if (null == $data || !is_array($data)) {
            throw BadIdentifier::for($identifier);
        }

        $new = $data['left_usages'] - 1;

        if ($new < 1) {
            $res = $this->cache->delete($key);

            if (true !== $res) {
                throw new UnavailableStorage("Could not delete cache key [$key].");
            }
        } else {
            $data['left_usages'] = $new;

            $ttl = $this->ttlInSeconds($data['expires_at']);

            $res = $this->cache->set($key, $data, $ttl);

            if (true !== $res) {
                throw new UnavailableStorage("Cant decrement usage for signed url [$identifier].");
            }
        }
    }

    private function buildCacheKey(string $identifier): string
    {
        return 'signed_url_' . $identifier;
    }

    private function ttlInSeconds(int $expires_at): int
    {
        // A link that was created at a hypothetical timestamp "1000" with a ttl
        // of 1 second should still be valid at timestamp "1001".
        // psr/simple-cache does not compare ttl like but considers only items as a hit where $expires_at > time().
        // This tho which is why we need to add one extra second.
        return ($expires_at - time()) + 1;
    }

    public function store(SignedUrl $signed_url): void
    {
        $key = $this->buildCacheKey($signed_url->identifier());

        $data = [
            'left_usages' => $signed_url->maxUsage(),
            'expires_at' => $signed_url->expiresAt(),
        ];

        $ttl = $this->ttlInSeconds($data['expires_at']);

        $res = $this->cache->set($key, $data, $ttl);

        if (true !== $res) {
            throw new UnavailableStorage("Cant save cache key [$key].");
        }
    }

    public function gc(): void
    {
        //
    }

}