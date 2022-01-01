<?php

declare(strict_types=1);

namespace Snicco\Core\Support;

use Webmozart\Assert\Assert;

/**
 * @interal
 */
final class CacheFile
{
    
    private string $file;
    
    public function __construct(string $dir, string $file_name)
    {
        Assert::readable($dir);
        Assert::stringNotEmpty($file_name, "The cache file name can not be empty.");
        $this->file = rtrim($dir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$file_name;
    }
    
    public function asString() :string
    {
        return $this->file;
    }
    
    public function isCreated() :bool
    {
        return is_file($this->file);
    }
    
}