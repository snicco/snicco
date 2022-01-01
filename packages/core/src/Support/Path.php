<?php

declare(strict_types=1);

namespace Snicco\Core\Support;

use Snicco\Support\Str;

final class Path
{
    
    private string $path;
    
    private function __construct(string $path)
    {
        $this->path = $path;
    }
    
    public static function fromString(string $path) :Path
    {
        return new Path(Path::sanitize($path));
    }
    
    private static function sanitize(string $path) :string
    {
        return ltrim($path, '/');
    }
    
    public function asString() :string
    {
        return '/'.$this->path;
    }
    
    public function withTrailingSlash() :Path
    {
        $new = clone $this;
        $new->path = rtrim($this->path, '/').'/';
        return $new;
    }
    
    public function withoutTrailingSlash() :Path
    {
        $new = clone $this;
        $new->path = rtrim($this->path, '/');
        return $new;
    }
    
    public function prepend(Path $path) :Path
    {
        $old_path = $this->asString();
        
        return Path::fromString($path->asString().$old_path);
    }
    
    public function append(Path $path) :Path
    {
        $old_path = $this->asString();
        
        return Path::fromString($old_path.$path->asString());
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
    
}