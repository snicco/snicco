<?php

declare(strict_types=1);

namespace Snicco\SignedUrl\Storage;

use Snicco\SignedUrl\SignedUrl;
use Snicco\SignedUrl\Contracts\SignedUrlClock;
use Snicco\SignedUrl\Exceptions\BadIdentifier;
use Snicco\SignedUrl\Contracts\SignedUrlStorage;
use Snicco\Session\ValueObjects\ClockUsingDateTimeImmutable;

final class InMemoryStorage implements SignedUrlStorage
{
    
    /**
     * @var array<string,SignedUrl>
     */
    private $links = [];
    
    /**
     * @var SignedUrlClock
     */
    private $clock;
    
    public function __construct(?SignedUrlClock $clock = null)
    {
        $this->clock = $clock ?? new ClockUsingDateTimeImmutable();
    }
    
    public function gc() :void
    {
        foreach ($this->links as $key => $link) {
            if ($link['expires_at'] < $this->clock->currentTimestamp()) {
                unset($this->links[$key]);
            }
        }
    }
    
    public function store(SignedUrl $signed_url) :void
    {
        $this->links[$signed_url->identifier()] = [
            'expires_at' => $signed_url->expiresAt(),
            'usages_left' => $signed_url->maxUsage(),
        ];
    }
    
    public function all() :array
    {
        return $this->links;
    }
    
    public function decrementUsage(string $identifier) :void
    {
        if ( ! isset($this->links[$identifier])) {
            throw BadIdentifier::for($identifier);
        }
        
        $prev = $this->links[$identifier]['usages_left'];
        $new = $prev - 1;
        
        if ($new < 1) {
            unset($this->links[$identifier]);
        }
        else {
            $this->links[$identifier]['usages_left'] = $new;
        }
    }
    
    public function remainingUsage(string $identifier) :int
    {
        if ( ! isset($this->links[$identifier])) {
            return 0;
        }
        
        return $this->links[$identifier]['usages_left'];
    }
    
}