<?php

declare(strict_types=1);

namespace Snicco\Session\ValueObjects;

use InvalidArgumentException;

use function ltrim;
use function is_int;
use function implode;
use function sprintf;
use function ucfirst;
use function in_array;
use function strtolower;

/**
 * @api
 */
final class SessionConfig
{
    
    /**
     * @var string
     */
    private $path;
    
    /**
     * @var string
     */
    private $cookie_name;
    
    /**
     * @var string|null
     */
    private $cookie_domain;
    
    /**
     * @var string
     */
    private $same_site;
    
    /**
     * @var bool
     */
    private $http_only;
    
    /**
     * @var bool
     */
    private $only_secure;
    
    /**
     * @var int|null
     */
    private $absolute_lifetime_in_sec;
    
    /**
     * @var int
     */
    private $idle_timeout;
    
    /**
     * @var int
     */
    private $rotation_interval;
    
    /**
     * @var int
     */
    private $gc_percentage;
    
    public function __construct(array $config)
    {
        $this->path = isset($config['path'])
            ? '/'.ltrim($config['path'], '/')
            : '/';
        
        if ( ! isset($config['cookie_name'])) {
            throw new InvalidArgumentException("A cookie name is required");
        }
        else {
            $this->cookie_name = $config['cookie_name'];
        }
        
        $this->absolute_lifetime_in_sec = $config['absolute_lifetime_in_sec'] ?? null;
        
        if ( ! isset($config['idle_timeout_in_sec'])) {
            throw new InvalidArgumentException("An idle timeout is required.");
        }
        else {
            $this->idle_timeout = $config['idle_timeout_in_sec'];
        }
        
        $this->cookie_domain = $config['domain'] ?? null;
        
        $same_site = ucfirst(strtolower($config['same_site'] ?? 'Lax'));
        if ($same_site === 'None') {
            $same_site = 'None; Secure';
        }
        
        if ( ! in_array($same_site, $req = ['Lax', 'Strict', 'None; Secure'])) {
            throw new InvalidArgumentException(
                sprintf("same_site must be one of [%s].", implode(', ', $req))
            );
        }
        else {
            $this->same_site = $same_site;
        }
        
        if ( ! isset($config['rotation_interval_in_sec'])) {
            throw new InvalidArgumentException('A rotation interval is required.');
        }
        else {
            $this->rotation_interval = $config['rotation_interval_in_sec'];
        }
        
        $gc_percentage = $config['garbage_collection_percentage'] ?? -1;
        if ( ! is_int($gc_percentage) || $gc_percentage < 0 || $gc_percentage > 100) {
            throw new InvalidArgumentException(
                "The garbage collection percentage has to be between 0 and 100."
            );
        }
        $this->gc_percentage = $gc_percentage;
        
        $this->http_only = $config['http_only'] ?? true;
        $this->only_secure = $config['secure'] ?? true;
    }
    
    public static function fromDefaults(string $cookie_name) :SessionConfig
    {
        return new SessionConfig([
            'path' => '/',
            'cookie_name' => $cookie_name,
            'domain' => null,
            'http_only' => true,
            'secure' => true,
            'same_site' => 'lax',
            'idle_timeout_in_sec' => 60 * 15,
            'rotation_interval_in_sec' => 60 * 10,
            'garbage_collection_percentage' => 2,
        ]);
    }
    
    public function cookiePath() :string
    {
        return $this->path;
    }
    
    public function cookieName()
    {
        return $this->cookie_name;
    }
    
    public function cookieDomain() :?string
    {
        return $this->cookie_domain;
    }
    
    public function sameSite() :string
    {
        return $this->same_site;
    }
    
    public function onlyHttp()
    {
        return $this->http_only;
    }
    
    public function onlySecure() :bool
    {
        return $this->only_secure;
    }
    
    public function absoluteLifetimeInSec() :?int
    {
        return $this->absolute_lifetime_in_sec;
    }
    
    public function idleTimeoutInSec() :int
    {
        return $this->idle_timeout;
    }
    
    public function rotationInterval() :int
    {
        return $this->rotation_interval;
    }
    
    public function gcLottery() :SessionLottery
    {
        return new SessionLottery($this->gc_percentage);
    }
    
}