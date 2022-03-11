<?php

declare(strict_types=1);

namespace Snicco\Bundle\Debug\Tests\Displayer;

use PHPUnit\Framework\TestCase;
use Snicco\Bundle\Debug\Displayer\WhoopsJsonDisplayer;
use Whoops\Run;

/**
 * @internal
 */
final class WhoopsJsonDisplayerTest extends TestCase
{
    /**
     * @test
     */
    public function test_is_verbose(): void
    {
        $displayer = new WhoopsJsonDisplayer(new Run());
        $this->assertTrue($displayer->isVerbose());
    }
}
