<?php

namespace Snicco\Core\Support;

use Symfony\Component\Finder\Finder;

final class DirectoryFinder
{
    
    /**
     * Get all the directories within a given directory.
     */
    public function directories(string $directory) :array
    {
        $directories = [];
        
        foreach (Finder::create()
                       ->in($directory)
                       ->directories()
                       ->depth(0)
                       ->sortByName() as $dir) {
            $directories[] = $dir->getPathname();
        }
        
        return $directories;
    }
    
}