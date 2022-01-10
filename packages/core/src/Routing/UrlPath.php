<?php

declare(strict_types=1);

namespace Snicco\Core\Routing;

use Snicco\Support\Str;

use function ltrim;
use function rtrim;

final class UrlPath
{
    
    // without leading slash
    private string $path;
    
    private function __construct(string $path)
    {
        $this->path = $path;
    }
    
    public static function fromString(string $path) :UrlPath
    {
        return new UrlPath(UrlPath::sanitize($path));
    }
    
    private static function sanitize(string $path) :string
    {
        if (Str::endsWith($path, '//')) {
            $path = rtrim($path, '/').'/';
        }
        
        return ltrim($path, '/');
    }
    
    public function asString() :string
    {
        return '/'.$this->path;
    }
    
    public function withTrailingSlash() :UrlPath
    {
        $new = clone $this;
        $new->path = rtrim($this->path, '/').'/';
        return $new;
    }
    
    public function withoutTrailingSlash() :UrlPath
    {
        $new = clone $this;
        $new->path = rtrim($this->path, '/');
        return $new;
    }
    
    /**
     * @param  string|UrlPath  $path
     */
    public function prepend($path) :UrlPath
    {
        $path = is_string($path) ? UrlPath::fromString($path) : $path;
        
        return UrlPath::fromString(rtrim($path->asString(), '/').$this->asString());
    }
    
    /**
     * @param  string|UrlPath  $path
     */
    public function append($path) :UrlPath
    {
        $path = is_string($path) ? UrlPath::fromString($path) : $path;
        
        return UrlPath::fromString($this->asString().$path->asString());
    }
    
    public function equals(string $path) :bool
    {
        return $this->asString() === '/'.ltrim($path, '/');
    }
    
    public function contains(string $path) :bool
    {
        $path = trim($path, '/');
        return Str::contains($this->asString(), $path);
    }
    
    public function __toString() :string
    {
        return $this->asString();
    }
    
}