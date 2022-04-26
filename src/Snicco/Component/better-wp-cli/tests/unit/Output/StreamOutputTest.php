<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPCLI\Tests\unit\Output;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Snicco\Component\BetterWPCLI\Output\StreamOutput;
use Snicco\Component\BetterWPCLI\Verbosity;
use stdClass;

use function curl_init;
use function fopen;
use function putenv;

use const STDOUT;

/**
 * @internal
 */
final class StreamOutputTest extends TestCase
{
    /**
     * @var resource
     */
    private $stream;

    protected function setUp(): void
    {
        $stream = fopen('php://memory', 'a', false);
        if (false === $stream) {
            throw new RuntimeException('Could not option stream');
        }

        $this->stream = $stream;
    }

    /**
     * @test
     */
    public function exception_for_non_resource(): void
    {
        $this->expectException(InvalidArgumentException::class);
        /**
         * @psalm-suppress InvalidArgument
         */
        new StreamOutput(new stdClass());
    }

    /**
     * @test
     */
    public function exception_for_non_stream_resource(): void
    {
        $handle = curl_init('https://localhost.com');
        $this->expectException(InvalidArgumentException::class);
        /**
         * @psalm-suppress PossiblyFalseArgument
         */
        new StreamOutput($handle);
    }

    /**
     * @test
     */
    public function that_do_write_works(): void
    {
        $output = new StreamOutput($this->stream);
        $output->writeln('foo');
        rewind($this->stream);
        $this->assertEquals('foo' . PHP_EOL, stream_get_contents($this->stream), '->doWrite() writes to the stream');
    }

    /**
     * @test
     */
    public function that_do_write_does_nothing_on_failure(): void
    {
        $stream = fopen('php://memory', 'r', false);
        if (false === $stream) {
            throw new RuntimeException('Could not option stream');
        }

        $output = new StreamOutput($stream);
        $output->writeln('foo');
        rewind($stream);
        $this->assertSame('', stream_get_contents($stream));

        unset($stream);
    }

    /**
     * @test
     */
    public function test_colors_with_explicit_arguments(): void
    {
        $output = new StreamOutput($this->stream, Verbosity::NORMAL, true);
        $this->assertTrue($output->supportsDecoration());

        $output = new StreamOutput($this->stream, Verbosity::NORMAL, false);
        $this->assertFalse($output->supportsDecoration());
    }

    /**
     * @test
     */
    public function that_no_colors_overwrites_color_settings_derived_from_stream(): void
    {
        try {
            $_SERVER['NO_COLOR'] = true;
            $output = new StreamOutput(STDOUT);
            $this->assertFalse($output->supportsDecoration());
        } finally {
            unset($_SERVER['NO_COLOR']);
        }

        try {
            putenv('NO_COLOR=true');
            $output = new StreamOutput(STDOUT);
            $this->assertFalse($output->supportsDecoration());
        } finally {
            putenv('NO_COLOR');
        }
    }

    /**
     * @test
     */
    public function colors_with_hyper_term_programm(): void
    {
        try {
            putenv('TERM_PROGRAM=Hyper');
            $output = new StreamOutput($this->stream);
            $this->assertTrue($output->supportsDecoration());
        } finally {
            putenv('TERM_PROGRAM');
        }
    }
}
