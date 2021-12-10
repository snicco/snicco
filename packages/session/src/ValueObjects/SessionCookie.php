<?php

declare(strict_types=1);

namespace Snicco\Session\ValueObjects;

use Snicco\Session\Contracts\SessionManagerInterface;

use function time;
use function is_null;

/**
 * @api
 */
final class SessionCookie
{
    
    /**
     * @var string
     */
    private $cookie_name;
    /**
     * @var string
     */
    private $session_id;
    
    /**
     * @var int|null
     */
    private $life_time;
    
    /**
     * @var bool
     */
    private $http_only;
    
    /**
     * @var bool
     */
    private $secure;
    
    /**
     * @var string
     */
    private $path;
    
    /**
     * @var string|null
     */
    private $domain;
    
    /**
     * @var string
     */
    private $same_site;
    
    /**
     * @interal This class MUST only be constructed with {@see SessionManagerInterface::toCookie()}
     */
    public function __construct(
        string $cookie_name,
        string $session_id,
        ?int $life_time = null,
        bool $http_only = true,
        bool $secure = true,
        string $path = '/',
        string $domain = null,
        string $same_site = 'Lax'
    ) {
        $this->cookie_name = $cookie_name;
        $this->session_id = $session_id;
        $this->life_time = $life_time;
        $this->http_only = $http_only;
        $this->secure = $secure;
        $this->path = $path;
        $this->domain = $domain;
        $this->same_site = $same_site;
    }
    
    public function value() :string
    {
        return $this->session_id;
    }
    
    public function name() :string
    {
        return $this->cookie_name;
    }
    
    /**
     * @return string Possible values: "Lax", "Strict", "None; Secure"
     */
    public function sameSite() :string
    {
        return $this->same_site;
    }
    
    public function path() :string
    {
        return $this->path;
    }
    
    public function domain() :?string
    {
        return $this->domain;
    }
    
    public function secureOnly() :bool
    {
        return $this->secure;
    }
    
    public function httpOnly() :bool
    {
        return $this->http_only;
    }
    
    public function expiryTimestamp() :int
    {
        if (is_null($this->life_time)) {
            return 0;
        }
        
        return time() + $this->life_time;
    }
    
    public function lifetime() :?int
    {
        return $this->life_time;
    }
    
}