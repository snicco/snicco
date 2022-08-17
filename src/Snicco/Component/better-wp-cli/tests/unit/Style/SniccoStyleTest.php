<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPCLI\Tests\unit\Style;

use BadMethodCallException;
use Closure;
use FilesystemIterator;
use Generator;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Snicco\Component\BetterWPCLI\Input\Input;
use Snicco\Component\BetterWPCLI\Output\ConsoleOutputInterface;
use Snicco\Component\BetterWPCLI\Output\Output;
use Snicco\Component\BetterWPCLI\Output\OutputWithVerbosity;
use Snicco\Component\BetterWPCLI\Output\StreamOutput;
use Snicco\Component\BetterWPCLI\Style\BG;
use Snicco\Component\BetterWPCLI\Style\SniccoStyle;
use Snicco\Component\BetterWPCLI\Style\Text;
use Snicco\Component\BetterWPCLI\Tests\fixtures\TestOutput;
use Snicco\Component\BetterWPCLI\Verbosity;

use SplFileInfo;

use function basename;
use function dirname;
use function file_get_contents;
use function fopen;
use function putenv;
use function rewind;
use function str_repeat;
use function stream_get_contents;

use const PHP_EOL;

/**
 * @internal
 */
final class SniccoStyleTest extends TestCase
{
    private string $initial_col_size;

    /**
     * @var resource
     */
    private $stream;

    protected function setUp(): void
    {
        $this->initial_col_size = (string) getenv('COLUMNS');
        $res = putenv('COLUMNS=117');

        if (! $res) {
            throw new RuntimeException('Could not update COLUMNS env var.');
        }

        $stream = fopen('php://memory', 'w', false);
        if (false === $stream) {
            throw new RuntimeException('Could not option stream');
        }

        $this->stream = $stream;
    }

    protected function tearDown(): void
    {
        $res = putenv('' !== $this->initial_col_size ? 'COLUMNS=' . $this->initial_col_size : 'COLUMNS');

        if (! $res) {
            throw new RuntimeException('Could not update COLUMNS env var.');
        }
    }

    /**
     * @test
     */
    public function that_all_methods_defined_in_the_output_interface_write_to_stderr_if_a_console_output_is_passed(
    ): void {
        $stdout = new TestOutput();
        $stderr = new TestOutput();
        $style = new SniccoStyle(new DummyInput(), new TestMultiStreamOutput($stdout, $stderr));

        $style->text('foo');

        $this->assertSame([PHP_EOL, ' foo' . PHP_EOL], $stderr->lines);

        $this->assertSame([], $stdout->lines);
    }

    /**
     * @test
     */
    public function that_writing_to_stderr_can_be_prevented(): void
    {
        $stdout = new TestOutput();
        $stderr = new TestOutput();
        $style = new SniccoStyle(new DummyInput(), new TestMultiStreamOutput($stdout, $stderr), false);

        $style->text('foo');

        $this->assertSame([PHP_EOL, ' foo' . PHP_EOL], $stdout->lines);

        $this->assertSame([], $stderr->lines);
    }

    /**
     * @test
     */
    public function that_auto_prepending_works(): void
    {
        $style = new SniccoStyle(new DummyInput(), $output = new TestOutput());

        $output->write('foo');
        $style->title('Title');

        $this->assertSame(['foo', PHP_EOL, 'Title' . PHP_EOL, '=====' . PHP_EOL, PHP_EOL], $output->lines);

        $style->title('Title2');

        $this->assertSame([
            'foo',
            PHP_EOL,
            'Title' . PHP_EOL,
            '=====' . PHP_EOL,
            PHP_EOL,
            'Title2' . PHP_EOL,
            '======' . PHP_EOL,
            PHP_EOL,
        ], $output->lines);
    }

    /**
     * @test
     */
    public function that_text_can_be_colorized(): void
    {
        $style = new SniccoStyle(new DummyInput(), new TestOutput(Verbosity::NORMAL, true));

        $res = $style->colorize('foo', Text::GREEN, BG::RED);
        $this->assertSame('[41m[32mfoo[0m', $res);

        $style = new SniccoStyle(new DummyInput(), new TestOutput(Verbosity::NORMAL, false));

        $res = $style->colorize('bar', Text::GREEN, BG::RED);
        $this->assertSame('bar', $res);
    }

    /**
     * @test
     *
     * @param  Closure(SniccoStyle,Output=):void  $command
     *
     * @dataProvider commandAndOutputProvider
     */
    public function that_all_commands_work(Closure $command, string $output_file): void
    {
        $style = new SniccoStyle(new DummyInput(), $output = new StreamOutput($this->stream, Verbosity::NORMAL, false));
        $command($style, $output);

        $name = basename($output_file);

        $this->assertSame(
            $this->getFixtureContent($output_file),
            $this->getStreamContent($this->stream),
            sprintf('Incorrect output for %s', $name)
        );
    }

    /**
     * @test
     *
     * @param  Closure(SniccoStyle,Output=):void  $command
     *
     * @dataProvider coloredCommandAndOutputProvider
     */
    public function that_all_commands_work_with_colors(Closure $command, string $output_file): void
    {
        $style = new SniccoStyle(new DummyInput(), $output = new StreamOutput($this->stream, Verbosity::NORMAL, true));
        $command($style, $output);

        $name = basename($output_file);

        $this->assertSame(
            $this->getFixtureContent($output_file),
            $this->getStreamContent($this->stream),
            sprintf('Incorrect output for %s', $name)
        );
    }

    /**
     * @test
     */
    public function that_it_does_not_fail_for_small_windows_sizes_and_long_messages(): void
    {
        try {
            putenv('COLUMNS=10');

            $style = new SniccoStyle(
                new DummyInput(),
                $output = new TestOutput()
            );

            $style->success(str_repeat('x', 100));

            $this->assertNotEmpty($output->lines);
        } finally {
            putenv("COLUMNS={$this->initial_col_size}");
        }
    }

    /**
     * @psalm-suppress UnresolvableInclude
     */
    public function commandAndOutputProvider(): Generator
    {
        $style_dir = dirname(__DIR__, 2) . '/fixtures/style';
        $output_dir = dirname(__DIR__, 2) . '/fixtures/output';

        $iterator = new FilesystemIterator($style_dir);

        /**
         * @var SplFileInfo $file
         */
        foreach ($iterator as $file) {
            yield [require $file->getRealPath(), sprintf('%s/%s.txt', $output_dir, $file->getBasename('.php'))];
        }
    }

    /**
     * @psalm-suppress UnresolvableInclude
     */
    public function coloredCommandAndOutputProvider(): Generator
    {
        $style_dir = dirname(__DIR__, 2) . '/fixtures/style-colored';
        $output_dir = dirname(__DIR__, 2) . '/fixtures/output-colored';

        $iterator = new FilesystemIterator($style_dir);

        /**
         * @var SplFileInfo $file
         */
        foreach ($iterator as $file) {
            $name = $file->getBasename('.php');

            yield [require $file->getRealPath(), sprintf('%s/%s.txt', $output_dir, $name)];
        }
    }

    /**
     * @param resource $stream
     */
    private function getStreamContent($stream): string
    {
        $res = rewind($stream);
        if (! $res) {
            throw new RuntimeException('Could not rewind stream');
        }

        $content = stream_get_contents($stream);
        if (false === $content) {
            throw new RuntimeException('Could not get stream contents');
        }

        return $content;
    }

    private function getFixtureContent(string $file): string
    {
        $contents = file_get_contents($file);
        if (false === $contents) {
            throw new RuntimeException('Could not read fixture file');
        }

        return $contents;
    }
}

final class TestMultiStreamOutput extends OutputWithVerbosity implements ConsoleOutputInterface
{
    private Output $stdout;

    private Output $stderr;

    public function __construct(OutputWithVerbosity $stdout, OutputWithVerbosity $stderr)
    {
        parent::__construct();
        $this->stdout = $stdout;
        $this->stderr = $stderr;
    }

    public function errorOutput(): Output
    {
        return $this->stderr;
    }

    protected function doWrite(string $message, bool $newline): void
    {
        $this->stdout->write($message, $newline);
    }
}

/**
 * @psalm-immutable
 */
final class DummyInput implements Input
{
    public function getArgument(string $name, ?string $default = null): ?string
    {
        throw new BadMethodCallException(__METHOD__);
    }

    public function getRepeatingArgument(string $name, ?array $default = null): ?array
    {
        throw new BadMethodCallException(__METHOD__);
    }

    public function getOption(string $name, ?string $default = null): ?string
    {
        throw new BadMethodCallException(__METHOD__);
    }

    public function getFlag(string $name, ?bool $default = null): ?bool
    {
        throw new BadMethodCallException(__METHOD__);
    }

    public function isInteractive(): bool
    {
        throw new BadMethodCallException(__METHOD__);
    }

    public function getStream(): void
    {
        throw new BadMethodCallException(__METHOD__);
    }

    public function getArguments(): array
    {
        throw new BadMethodCallException(__METHOD__);
    }

    public function getRepeatingArguments(): array
    {
        throw new BadMethodCallException(__METHOD__);
    }

    public function getOptions(): array
    {
        throw new BadMethodCallException(__METHOD__);
    }

    public function getFlags(): array
    {
        throw new BadMethodCallException(__METHOD__);
    }
}
