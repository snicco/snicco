<?php

declare(strict_types=1);

namespace Snicco\Http;

use LogicException;
use DateTimeInterface;
use InvalidArgumentException;

class Cookie
{
    
    private array  $defaults = [
        'value' => '',
        'domain' => null,
        'hostonly' => true,
        'path' => '/',
        'expires' => null,
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Lax',
    ];
    private array  $properties;
    private string $name;
    
    public function __construct(string $name, string $value, bool $url_encode = true)
    {
        $this->name = $name;
        
        $value = ['value' => $url_encode ? urlencode($value) : $value];
        
        $this->properties = array_merge($this->defaults, $value);
    }
    
    public function properties() :array
    {
        return $this->properties;
    }
    
    public function setProperties(array $array)
    {
        $this->properties = array_merge($this->properties, $array);
    }
    
    public function name() :string
    {
        return $this->name;
    }
    
    public function allowJs() :Cookie
    {
        $this->properties['httponly'] = false;
        
        return $this;
    }
    
    public function onlyHttp() :Cookie
    {
        $this->properties['httponly'] = true;
        return $this;
    }
    
    public function allowUnsecure() :Cookie
    {
        $this->properties['secure'] = false;
        
        return $this;
    }
    
    public function path(string $path) :Cookie
    {
        $this->properties['path'] = $path;
        
        return $this;
    }
    
    public function domain(?string $domain) :Cookie
    {
        $this->properties['domain'] = $domain;
        
        return $this;
    }
    
    public function sameSite(string $same_site) :Cookie
    {
        $same_site = ucwords($same_site);
        
        if ( ! in_array($same_site, ['Lax', 'Strict', 'None'])) {
            throw new LogicException(
                "The value [$same_site] is not supported for the SameSite cookie."
            );
        }
        
        $this->properties['samesite'] = $same_site;
        
        if ($same_site === 'None') {
            $this->properties['secure'] = true;
        }
        
        return $this;
    }
    
    /**
     * @param  int|DateTimeInterface|$timestamp
     */
    public function expires($timestamp) :Cookie
    {
        if ( ! is_int($timestamp) && ! $timestamp instanceof DateTimeInterface) {
            throw new InvalidArgumentException('timestamp must be an integer or DataTimeInterface');
        }
        
        $timestamp = $timestamp instanceof DateTimeInterface
            ? $timestamp->getTimestamp()
            : $timestamp;
        
        $this->properties['expires'] = $timestamp;
        
        return $this;
    }
    
}