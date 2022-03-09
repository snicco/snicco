<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Testing;

use Snicco\Component\StrArr\Str;

use function explode;
use function trim;

/**
 * @psalm-immutable
 */
final class AssertableCookie
{
    public string $value;

    public string $name;

    public string $path = '';

    public string $expires = '';

    public bool $secure = false;

    public bool $http_only = false;

    public string $same_site = '';

    public function __construct(string $set_cookie_header)
    {
        $parts = explode('; ', $set_cookie_header);

        $this->name = Str::beforeFirst($parts[0] ?? '', '=');
        $this->value = Str::afterFirst($parts[0] ?? '', '=');

        foreach ($parts as $part) {
            $part = trim($part);
            if (Str::startsWith($part, 'Path')) {
                $this->path = Str::afterFirst($part, '=');
                continue;
            }
            if (Str::startsWith($part, 'SameSite')) {
                $this->same_site = Str::afterFirst($part, '=');
                continue;
            }
            if (Str::startsWith($part, 'Expires')) {
                $this->expires = Str::afterFirst($part, '=');
                continue;
            }
            if ($part === 'Secure') {
                $this->secure = true;
                continue;
            }
            if ($part === 'HttpOnly') {
                $this->http_only = true;
            }
        }
    }
}
