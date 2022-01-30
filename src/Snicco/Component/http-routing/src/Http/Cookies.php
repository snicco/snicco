<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Http;

use function gmdate;
use function in_array;
use function is_string;
use function strtotime;
use function urlencode;
use function strtolower;

/**
 * @api
 */
final class Cookies
{
    
    /**
     * @var array<string,Cookie>
     */
    private array $response_cookies = [];
    
    public function withCookie(Cookie $cookie) :Cookies
    {
        $new = clone $this;
        $new->response_cookies[$cookie->name()] = $cookie->properties();
        return $new;
    }
    
    /**
     * @return string[]
     */
    public function toHeaders() :array
    {
        $headers = [];
        
        foreach ($this->response_cookies as $name => $properties) {
            $headers[] = $this->toHeader($name, $properties);
        }
        
        return $headers;
    }
    
    private function toHeader(string $name, array $properties) :string
    {
        $header = urlencode($name).'='.$properties['value'];
        
        if (isset($properties['domain'])) {
            $header .= '; domain='.$properties['domain'];
        }
        
        if (isset($properties['path'])) {
            $header .= '; path='.$properties['path'];
        }
        
        if (isset($properties['expires'])) {
            if (is_string($properties['expires'])) {
                $timestamp = strtotime($properties['expires']);
            }
            else {
                $timestamp = (int) $properties['expires'];
            }
            if ($timestamp && $timestamp !== 0) {
                $header .= '; expires='.gmdate('D, d-M-Y H:i:s e', $timestamp);
            }
        }
        
        if (isset($properties['secure']) && $properties['secure']) {
            $header .= '; secure';
        }
        
        if (isset($properties['hostonly']) && $properties['hostonly']) {
            $header .= '; HostOnly';
        }
        
        if (isset($properties['httponly']) && $properties['httponly']) {
            $header .= '; HttpOnly';
        }
        
        if (isset($properties['samesite'])
            && in_array(
                strtolower($properties['samesite']),
                ['lax', 'strict'],
                true
            )) {
            // While strtolower is needed for correct comparison, the RFC doesn't care about case
            $header .= '; SameSite='.$properties['samesite'];
        }
        
        return $header;
    }
    
}