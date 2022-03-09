<?php

declare(strict_types=1);

namespace Snicco\Component\Kernel\Tests\helpers;

use FilesystemIterator;
use SplFileInfo;

use function unlink;

/**
 * @interal
 */
trait CleanDirs
{
    /**
     * @param string[] $dirs
     */
    protected function cleanDirs(array $dirs): void
    {
        foreach ($dirs as $dir) {
            $iterator = new FilesystemIterator($dir);

            /** @var SplFileInfo $file */
            foreach ($iterator as $file) {
                if ('php' === $file->getExtension()) {
                    unlink($file->getRealPath());
                }
            }
        }
    }
}
