<?php

namespace Snicco\Component\Core\Utils;

use Symfony\Component\Finder\Finder;

/**
 * @framework-only
 */
final class DirectoryFinder
{

    /**
     * @return string[]
     */
    public function allDirsInDir(string $directory): array
    {
        $directories = [];

        foreach (
            Finder::create()
                ->in($directory)
                ->directories()
                ->depth(0)
                ->sortByName() as $dir
        ) {
            $directories[] = $dir->getPathname();
        }

        return $directories;
    }

}