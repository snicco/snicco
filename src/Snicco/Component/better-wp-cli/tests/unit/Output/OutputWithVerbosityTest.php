<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPCLI\Tests\unit\Output;

use PHPUnit\Framework\TestCase;
use Snicco\Component\BetterWPCLI\Tests\fixtures\TestOutput;
use Snicco\Component\BetterWPCLI\Verbosity;

use const PHP_EOL;

/**
 * @internal
 */
final class OutputWithVerbosityTest extends TestCase
{
    /**
     * @test
     */
    public function that_write_works_correctly(): void
    {
        $test_output = new TestOutput();

        $test_output->write('foo');
        $this->assertSame(['foo'], $test_output->lines);

        $test_output->write('bar', true);
        $this->assertSame(['foo', 'bar' . PHP_EOL], $test_output->lines);

        $test_output->write(['baz', 'biz'], true);
        $this->assertSame(['foo', 'bar' . PHP_EOL, 'baz' . PHP_EOL, 'biz' . PHP_EOL], $test_output->lines);
    }

    /**
     * @test
     */
    public function that_writeln_works_correctly(): void
    {
        $test_output = new TestOutput();

        $test_output->writeln('foo');
        $this->assertSame(['foo' . PHP_EOL], $test_output->lines);

        $test_output->writeln('bar');
        $this->assertSame(['foo' . PHP_EOL, 'bar' . PHP_EOL], $test_output->lines);

        $test_output->write(['baz', 'biz'], true);
        $this->assertSame(['foo' . PHP_EOL, 'bar' . PHP_EOL, 'baz' . PHP_EOL, 'biz' . PHP_EOL], $test_output->lines);
    }

    /**
     * @test
     */
    public function that_newline_works_correctly(): void
    {
        $test_output = new TestOutput();
        $test_output->newLine();
        $this->assertSame([PHP_EOL], $test_output->lines);

        $test_output = new TestOutput();
        $test_output->newLine(3);
        $this->assertSame([PHP_EOL . PHP_EOL . PHP_EOL], $test_output->lines);
    }

    /**
     * @test
     */
    public function that_output_is_only_written_if_the_message_verbosity_is_not_greater_than_the_output_verbosity(): void
    {
        $test_output = new TestOutput(Verbosity::NORMAL);

        $test_output->writeln('foo', 0);
        $this->assertSame(['foo' . PHP_EOL], $test_output->lines);

        $test_output->writeln('bar', Verbosity::NORMAL);
        $this->assertSame(['foo' . PHP_EOL, 'bar' . PHP_EOL], $test_output->lines);

        $test_output->writeln('baz', Verbosity::VERBOSE);
        $this->assertSame(['foo' . PHP_EOL, 'bar' . PHP_EOL], $test_output->lines);
    }

    /**
     * @test
     */
    public function that_not_decorated_by_default(): void
    {
        $test_output = new TestOutput();
        $this->assertFalse($test_output->supportsDecoration());

        $test_output = new TestOutput(Verbosity::NORMAL, true);
        $this->assertTrue($test_output->supportsDecoration());
    }

    /**
     * @test
     */
    public function test_verbosities(): void
    {
        $output = new TestOutput(Verbosity::QUIET);
        $this->assertTrue($output->isQuiet());
        $this->assertFalse($output->isVerbose());
        $this->assertFalse($output->isVeryVerbose());
        $this->assertFalse($output->isDebug());

        $output = new TestOutput(Verbosity::NORMAL);
        $this->assertFalse($output->isQuiet());
        $this->assertFalse($output->isVerbose());
        $this->assertFalse($output->isVeryVerbose());
        $this->assertFalse($output->isDebug());

        $output = new TestOutput(Verbosity::VERBOSE);
        $this->assertFalse($output->isQuiet());
        $this->assertTrue($output->isVerbose());
        $this->assertFalse($output->isVeryVerbose());
        $this->assertFalse($output->isDebug());

        $output = new TestOutput(Verbosity::VERY_VERBOSE);
        $this->assertFalse($output->isQuiet());
        $this->assertTrue($output->isVerbose());
        $this->assertTrue($output->isVeryVerbose());
        $this->assertFalse($output->isDebug());

        $output = new TestOutput(Verbosity::DEBUG);
        $this->assertFalse($output->isQuiet());
        $this->assertTrue($output->isVerbose());
        $this->assertTrue($output->isVeryVerbose());
        $this->assertTrue($output->isDebug());
    }
}
