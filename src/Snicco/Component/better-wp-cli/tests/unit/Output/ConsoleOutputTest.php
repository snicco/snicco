<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPCLI\Tests\unit\Output;

use PHPUnit\Framework\TestCase;
use Snicco\Component\BetterWPCLI\Output\ConsoleOutput;
use Snicco\Component\BetterWPCLI\Output\StreamOutput;
use Snicco\Component\BetterWPCLI\Verbosity;

use const STDERR;

/**
 * @internal
 */
final class ConsoleOutputTest extends TestCase
{
    /**
     * @test
     */
    public function a_stderr_output_can_be_retrieved(): void
    {
        $output = new ConsoleOutput();
        $this->assertEquals(new StreamOutput(STDERR), $output->errorOutput());
    }

    /**
     * @test
     */
    public function that_colors_are_passed_correctly(): void
    {
        $output = new ConsoleOutput(Verbosity::NORMAL, false, false);
        $this->assertFalse($output->supportsDecoration());
        $this->assertFalse($output->errorOutput()->supportsDecoration());

        $output = new ConsoleOutput(Verbosity::NORMAL, true, false);
        $this->assertTrue($output->supportsDecoration());
        $this->assertFalse($output->errorOutput()->supportsDecoration());

        $output = new ConsoleOutput(Verbosity::NORMAL, false, true);
        $this->assertFalse($output->supportsDecoration());
        $this->assertTrue($output->errorOutput()->supportsDecoration());
    }
}
