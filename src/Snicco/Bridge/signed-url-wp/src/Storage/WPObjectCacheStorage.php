<?php

declare(strict_types=1);

namespace Snicco\SignedUrlWP\Storage;

use RuntimeException;
use Snicco\Component\SignedUrl\SignedUrl;
use Snicco\Component\SignedUrl\Exception\BadIdentifier;
use Snicco\Component\SignedUrl\Contracts\SignedUrlClock;
use Snicco\Component\SignedUrl\Storage\SignedUrlStorage;
use Snicco\Component\SignedUrl\SignedUrlClockUsingDateTimeImmutable;

use function intval;
use function wp_cache_set;
use function wp_cache_get;
use function wp_cache_decr;
use function wp_cache_delete;

final class WPObjectCacheStorage implements SignedUrlStorage
{
    
    /**
     * @var string
     */
    private $cache_group;
    
    /**
     * @var SignedUrlClock
     */
    private $clock;
    
    public function __construct(string $cache_group, SignedUrlClock $clock = null)
    {
        $this->cache_group = $cache_group;
        $this->clock = $clock ?? new SignedUrlClockUsingDateTimeImmutable();
    }
    
    public function consume(string $identifier) :void
    {
        $left_usages = wp_cache_decr($identifier, 1, $this->cache_group);
        
        if ($left_usages === false) {
            wp_cache_get($identifier, $this->cache_group, true, $found);
            if ($found) {
                throw new RuntimeException(
                    "Cant decrement usage with wp_object storage for identifier [$identifier]."
                );
            }
            else {
                throw BadIdentifier::for($identifier);
            }
        }
        
        if ($left_usages < 1) {
            $removed = wp_cache_delete($identifier, $this->cache_group);
            if ($removed === false) {
                throw new RuntimeException(
                    "Cant remove key [$identifier] with wp_objet cache storage."
                );
            }
        }
    }
    
    public function remainingUsage(string $identifier) :int
    {
        $usage = wp_cache_get($identifier, $this->cache_group, false, $found);
        
        if ( ! $found || $usage === false) {
            return 0;
        }
        
        return intval($usage);
    }
    
    public function store(SignedUrl $signed_url) :void
    {
        $lifetime = $signed_url->expiresAt() - $this->clock->currentTimestamp();
        
        $success = wp_cache_set(
            $signed_url->identifier(),
            $signed_url->maxUsage(),
            $this->cache_group,
            $lifetime
        );
        
        if ( ! $success) {
            throw new RuntimeException(
                "Singed url for protected path [{$signed_url->protects()}] cant be stored."
            );
        }
    }
    
    public function gc() :void
    {
        // Nothing to do here.
    }
    
}