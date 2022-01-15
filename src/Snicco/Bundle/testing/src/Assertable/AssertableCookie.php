<?php

declare(strict_types=1);

namespace Snicco\Bundle\Testing\Assertable;

use Snicco\Component\StrArr\Str;
use PHPUnit\Framework\Assert as PHPUnit;

final class AssertableCookie
{
    
    private string $value;
    
    private string $path;
    
    private string $expires;
    
    private bool $secure;
    
    private bool $http_only;
    
    private string $same_site;
    
    private string $name;
    
    public function __construct(string $set_cookie_header)
    {
        $this->parseHeader($set_cookie_header);
        $this->name = Str::beforeFirst($set_cookie_header, '=');
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