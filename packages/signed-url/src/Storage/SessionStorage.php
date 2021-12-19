<?php

declare(strict_types=1);

namespace Snicco\SignedUrl\Storage;

use ArrayAccess;
use Snicco\SignedUrl\SignedUrl;
use Snicco\SignedUrl\Contracts\SignedUrlClock;
use Snicco\SignedUrl\Exceptions\BadIdentifier;
use Snicco\SignedUrl\Contracts\SignedUrlStorage;
use Snicco\SignedUrl\SignedUrlClockUsingDateTimeImmutable;

use function intval;

final class SessionStorage implements SignedUrlStorage
{
    
    private const namespace = '_signed_urls';
    
    /**
     * @var array|ArrayAccess
     */
    private $storage;
    
    /**
     * @var SignedUrlClock
     */
    private $clock;
    
    public function __construct(&$storage, SignedUrlClock $clock = null)
    {
        if ($storage instanceof ArrayAccess) {
            $this->storage = $storage;
        }
        else {
            $this->storage = &$storage;
        }
        $this->clock = $clock ?? new SignedUrlClockUsingDateTimeImmutable();
    }
    
    public function decrementUsage(string $identifier) :void
    {
        $left_usage = $this->remainingUsage($identifier);
        if ($left_usage === 0) {
            throw BadIdentifier::for($identifier);
        }
        
        $_temp = $this->storage[self::namespace];
        $_temp[$identifier]['left_usages'] = ($left_usage - 1);
        
        $this->storage[self::namespace] = $_temp;
    }
    
    public function remainingUsage(string $identifier) :int
    {
        if ( ! isset($this->storage[self::namespace][$identifier])) {
            return 0;
        }
        
        return intval($this->storage[self::namespace][$identifier]['left_usages'] ?? 0);
    }
    
    public function store(SignedUrl $signed_url) :void
    {
        $data = [
            'expires_at' => $signed_url->expiresAt(),
            'left_usages' => $signed_url->maxUsage(),
        ];
        
        // $_temp is needed to work with ArrayAccess
        $_temp = $this->storage[self::namespace] ?? [];
        $_temp[$signed_url->identifier()] = $data;
        
        $this->storage[self::namespace] = $_temp;
    }
    
    public function gc() :void
    {
        foreach ($this->storage[self::namespace] as $id => $signed_url) {
            if (($signed_url['expires_at'] ?? 0) < $this->clock->currentTimestamp()) {
                $_temp = $this->storage[self::namespace] ?? [];
                unset($_temp[$id]);
                $this->storage[self::namespace] = $_temp;
            }
        }
    }
    
}