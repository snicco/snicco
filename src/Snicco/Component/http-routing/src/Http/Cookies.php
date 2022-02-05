<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Http;

use function gmdate;
use function is_int;
use function is_string;
use function urlencode;

/**
 * @api
 * @psalm-immutable
 */
final class Cookies
{

    /**
     * @var array<string,Cookie>
     */
    private array $response_cookies = [];

    public function withCookie(Cookie $cookie): Cookies
    {
        $new = clone $this;
        $new->response_cookies[$cookie->name] = $cookie;
        return $new;
    }

    /**
     * @return string[]
     */
    public function toHeaders(): array
    {
        $headers = [];

        foreach ($this->response_cookies as $cookie) {
            $headers[] = $this->toHeader($cookie);
        }

        return $headers;
    }

    private function toHeader(Cookie $cookie): string
    {
        $properties = $cookie->properties;
        $header = urlencode($cookie->name) . '=' . urlencode($cookie->value);

        if (is_string($properties['domain'])) {
            $header .= '; domain=' . $properties['domain'];
        }

        if (is_string($properties['path'])) {
            $header .= '; path=' . $properties['path'];
        }

        if (is_int($properties['expires'])) {
            $header .= '; expires=' . gmdate('D, d-M-Y H:i:s e', $properties['expires']);
        }

        $header .= '; SameSite=' . $properties['samesite'];

        if ($properties['secure']) {
            $header .= '; secure';
        }

        if ($properties['hostonly']) {
            $header .= '; HostOnly';
        }

        if ($properties['httponly']) {
            $header .= '; HttpOnly';
        }

        return $header;
    }

}