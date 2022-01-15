<?php

declare(strict_types=1);

namespace Snicco\Core\Utils;

use Webmozart\Assert\Assert;

/**
 * @framework-only
 */
final class PHPCacheFile
{
    
    private CacheFile $cache_file;
    
    public function __construct(string $dir, string $filename_with_extension)
    {
        Assert::endsWith($filename_with_extension, '.php', 'The file extension must be [.php].');
        $this->cache_file = new CacheFile($dir, $filename_with_extension);
    }
    
    public function isCreated() :bool
    {
        return $this->cache_file->isCreated();
    }
    
    public function realPath() :string
    {
        return $this->cache_file->realpath();
    }
    
    /**
     * @return mixed
     */
    public function require()
    {
        return require $this->cache_file->realpath();
    }
    
}