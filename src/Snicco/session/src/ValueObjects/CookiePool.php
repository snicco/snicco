<?php

declare(strict_types=1);

namespace Snicco\Session\ValueObjects;

final class CookiePool
{
    
    /**
     * @var array
     */
    private $cookies;
    
    /**
     * @param  array<string,string>  $cookies
     *
     * @api
     */
    public function __construct(array $cookies)
    {
        $this->cookies = $cookies;
    }
    
    /**
     * @api
     */
    public static function fromSuperGlobals() :CookiePool
    {
        return new CookiePool($_COOKIE);
    }
    
    /**
     * @interal
     */
    public function has(string $cookie_name) :bool
    {
        return isset($this->cookies[$cookie_name]);
    }
    
    /**
     * @interal
     */
    public function get(string $cookie_name) :?string
    {
        return $this->cookies[$cookie_name] ?? null;
    }
    
}