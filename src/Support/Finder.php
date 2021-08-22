<?php

namespace Snicco\Support;

class Finder
{
    
    /**
     * Get all the directories within a given directory.
     */
    public function directories(string $directory) :array
    {
        $directories = [];
        
        foreach (\Symfony\Component\Finder\Finder::create()
                                                 ->in($directory)
                                                 ->directories()
                                                 ->depth(0)
                                                 ->sortByName() as $dir) {
            $directories[] = $dir->getPathname();
        }
        
        return $directories;
    }
    
}