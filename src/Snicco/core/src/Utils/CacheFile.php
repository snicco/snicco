<?php

declare(strict_types=1);

namespace Snicco\Core\Utils;

use RuntimeException;
use Webmozart\Assert\Assert;

/**
 * @note framework-only
 */
final class CacheFile
{
    
    private string $file;
    
    public function __construct(string $dir, string $filename_with_extension)
    {
        Assert::readable($dir);
        Assert::stringNotEmpty($filename_with_extension, "The cache file name can not be empty.");
        $this->file = rtrim($dir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$filename_with_extension;
    }
    
    public function realpath() :string
    {
        return $this->file;
    }
    
    public function isCreated() :bool
    {
        return is_file($this->file);
    }
    
    /**
     * @throws RuntimeException
     */
    public function getContents() :string
    {
        $val = file_get_contents($this->file);
        
        if (false === $val) {
            throw new RuntimeException("Cant read cache contents of file [$this->file]");
        }
        return $val;
    }
    
}