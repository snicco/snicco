<?php

/**
 * Modified version of Slims Cookie class
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim-Psr7/blob/master/LICENSE.md (MIT License)
 * @see https://github.com/slimphp/Slim-Psr7/blob/1.4/src/Cookies.php
 */

declare(strict_types=1);

namespace Snicco\Http;

use InvalidArgumentException;

use function count;
use function rtrim;
use function gmdate;
use function explode;
use function in_array;
use function is_array;
use function is_string;
use function strtotime;
use function urldecode;
use function urlencode;
use function preg_split;
use function strtolower;
use function array_replace;
use function array_key_exists;

class Cookies
{
    
    protected array $request_cookies = [];
    
    protected array $response_cookies = [];
    
    protected array $defaults = [
        'value' => '',
        'domain' => null,
        'hostonly' => true,
        'path' => '/',
        'expires' => null,
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Lax',
    ];
    
    public function __construct(array $cookies = [])
    {
        $this->request_cookies = $cookies;
    }
    
    /**
     * Parse cookie values from header value
     * Returns an associative array of cookie names and values
     *
     * @param  string|array  $header
     *
     * @return array
     */
    public static function parseHeader($header) :array
    {
        if (is_array($header)) {
            $header = isset($header[0]) ? $header[0] : '';
        }
        
        if ( ! is_string($header)) {
            throw new InvalidArgumentException(
                'Cannot parse Cookie data. Header value must be a string.'
            );
        }
        
        $header = rtrim($header, "\r\n");
        $pieces = preg_split('@[;]\s*@', $header);
        $cookies = [];
        
        if (is_array($pieces)) {
            foreach ($pieces as $cookie) {
                $cookie = explode('=', $cookie, 2);
                
                if (count($cookie) === 2) {
                    $key = urldecode($cookie[0]);
                    $value = urldecode($cookie[1]);
                    
                    if ( ! isset($cookies[$key])) {
                        $cookies[$key] = $value;
                    }
                }
            }
        }
        
        return $cookies;
    }
    
    /**
     * Set default cookie properties
     *
     * @param  array  $settings
     *
     * @return static
     */
    public function setDefaults(array $settings) :self
    {
        $this->defaults = array_replace($this->defaults, $settings);
        
        return $this;
    }
    
    /**
     * Get cookie
     *
     * @param  string  $name
     * @param  string|array|null  $default
     *
     * @return mixed|null
     */
    public function get(string $name, $default = null)
    {
        return array_key_exists($name, $this->request_cookies) ? $this->request_cookies[$name]
            : $default;
    }
    
    /**
     * Set cookie
     *
     * @param  string  $name
     * @param  string|array  $value
     *
     * @return static
     */
    public function set(string $name, $value) :self
    {
        if ( ! is_array($value)) {
            $value = ['value' => $value];
        }
        
        $this->response_cookies[$name] = array_replace($this->defaults, $value);
        
        return $this;
    }
    
    public function add(Cookie $cookie)
    {
        $this->response_cookies[$cookie->name()] = $cookie->properties();
        return $this;
    }
    
    /**
     * Convert all response cookies into an associate array of header values
     *
     * @return array
     */
    public function toHeaders() :array
    {
        $headers = [];
        
        foreach ($this->response_cookies as $name => $properties) {
            $headers[] = $this->toHeader($name, $properties);
        }
        
        return $headers;
    }
    
    /**
     * Convert to `Set-Cookie` header
     *
     * @param  string  $name  Cookie name
     * @param  array  $properties  Cookie properties
     *
     * @return string
     */
    protected function toHeader(string $name, array $properties) :string
    {
        $result = urlencode($name).'='.$properties['value'];
        
        if (isset($properties['domain'])) {
            $result .= '; domain='.$properties['domain'];
        }
        
        if (isset($properties['path'])) {
            $result .= '; path='.$properties['path'];
        }
        
        if (isset($properties['expires'])) {
            if (is_string($properties['expires'])) {
                $timestamp = strtotime($properties['expires']);
            }
            else {
                $timestamp = (int) $properties['expires'];
            }
            if ($timestamp && $timestamp !== 0) {
                $result .= '; expires='.gmdate('D, d-M-Y H:i:s e', $timestamp);
            }
        }
        
        if (isset($properties['secure']) && $properties['secure']) {
            $result .= '; secure';
        }
        
        if (isset($properties['hostonly']) && $properties['hostonly']) {
            $result .= '; HostOnly';
        }
        
        if (isset($properties['httponly']) && $properties['httponly']) {
            $result .= '; HttpOnly';
        }
        
        if (isset($properties['samesite'])
            && in_array(
                strtolower($properties['samesite']),
                ['lax', 'strict'],
                true
            )) {
            // While strtolower is needed for correct comparison, the RFC doesn't care about case
            $result .= '; SameSite='.$properties['samesite'];
        }
        
        return $result;
    }
    
}