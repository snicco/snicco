<?php

declare(strict_types=1);

namespace Snicco\Core\Routing;

use Webmozart\Assert\Assert;

final class AdminDashboardPrefix
{
    
    private string $prefix;
    
    private function __construct(string $prefix)
    {
        Assert::startsWith($prefix, '/');
        Assert::notEndsWith($prefix, '/');
        $this->prefix = $prefix;
    }
    
    public static function fromString(string $prefix) :AdminDashboardPrefix
    {
        return new self('/'.trim($prefix, '/'));
    }
    
    public function __toString()
    {
        return $this->prefix;
    }
    
    public function appendPath(string $path) :string
    {
        Assert::stringNotEmpty($path);
        return $this->prefix.'/'.ltrim($path, '/');
    }
    
}