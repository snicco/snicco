<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPCLI\Tests\unit\Output;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Snicco\Component\BetterWPCLI\Output\BufferedOutput;

use const PHP_EOL;

/**
 * @internal
 */
final class BufferedOutputTest extends TestCase
{
    /**
     * @test
     */
    public function test_exception_if_max_length_smaller_than_zero(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('positive integer');
        new BufferedOutput(0);
    }

    /**
     * @test
     */
    public function that_storing_and_fetching_messages_works(): void
    {
        $output = new BufferedOutput(2);

        $output->write('SNICCO');

        $this->assertSame('CO', $output->fetchAndEmpty());
        $this->assertSame('', $output->fetchAndEmpty());

        $output = new BufferedOutput(7);

        $output->writeln('SNICCO');

        $this->assertSame('SNICCO' . PHP_EOL, $output->fetchAndEmpty());
        $this->assertSame('', $output->fetchAndEmpty());
    }
}
