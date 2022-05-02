<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPCLI\Tests;

use RuntimeException;
use function fopen;

trait InMemoryStream
{
    /**
     * @return resource
     */
    private function getInMemoryStream()
    {
        $stream = fopen('php://memory', 'r+');
        if (false === $stream) {
            throw new RuntimeException('Could not open in memory stream.');
        }

        return $stream;
    }
}
