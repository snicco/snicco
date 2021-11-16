<?php

declare(strict_types=1);

namespace Snicco\Testing\Assertable;

use Snicco\Support\Str;
use PHPUnit\Framework\Assert as PHPUnit;

class AssertableCookie
{
    
    /**
     * @var string
     */
    private $value;
    
    /**
     * @var string
     */
    private $path;
    
    /**
     * @var string
     */
    private $expires;
    
    /**
     * @var bool
     */
    private $secure;
    
    /**
     * @var bool
     */
    private $http_only;
    
    /**
     * @var string
     */
    private $same_site;
    
    /**
     * @var string
     */
    private $name;
    
    public function __construct(string $set_cookie_header)
    {
        $this->parseHeader($set_cookie_header);
        $this->name = Str::before($set_cookie_header, '=');
    }
    
    public function assertValue(string $value) :AssertableCookie
    {
        PHPUnit::assertSame(
            $value,
            $this->value,
            "The [$this->name] cookie value [$value] does not match the actual value [$this->value]"
        );
        return $this;
    }
    
    private function parseHeader(string $set_cookie_header)
    {
        $this->value = Str::betweenFirst($set_cookie_header, '=', ';');
        $this->path = Str::betweenFirst($set_cookie_header, 'path=', ';');
        $this->expires = Str::betweenFirst($set_cookie_header, 'expires=', ';');
        $this->secure = Str::contains($set_cookie_header, 'secure');
        $this->http_only = Str::contains($set_cookie_header, 'HttpOnly');
        $this->same_site = Str::betweenFirst($set_cookie_header, 'SameSite=', ';');
    }
    
}