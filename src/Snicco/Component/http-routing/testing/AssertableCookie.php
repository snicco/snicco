<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Testing;

use Snicco\Component\StrArr\Str;

/**
 * @psalm-immutable
 */
final class AssertableCookie
{

    public string $value;

    public string $path;

    public string $expires;

    public bool $secure;

    public bool $http_only;

    public string $same_site;

    public string $name;

    public function __construct(string $set_cookie_header)
    {
        $this->name = Str::beforeFirst($set_cookie_header, '=');
        $this->value = Str::betweenFirst($set_cookie_header, '=', ';');
        $this->path = Str::betweenFirst($set_cookie_header, 'path=', ';');
        $this->expires = Str::betweenFirst($set_cookie_header, 'expires=', ';');
        $this->secure = Str::contains($set_cookie_header, 'secure');
        $this->http_only = Str::contains($set_cookie_header, 'HttpOnly');
        $this->same_site = Str::betweenFirst($set_cookie_header, 'SameSite=', ';');
    }

}