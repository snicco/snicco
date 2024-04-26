<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPCLI\Testing;

use Closure;
use PHPUnit\Framework\Assert;
use RuntimeException;
use Snicco\Component\BetterWPCLI\Command;
use Snicco\Component\BetterWPCLI\Input\WPCLIInput;
use Snicco\Component\BetterWPCLI\Output\StreamOutput;
use Snicco\Component\BetterWPCLI\Testing\Constraint\CommandIsSuccessful;
use Snicco\Component\BetterWPCLI\Testing\Constraint\InStream;
use Snicco\Component\BetterWPCLI\Testing\Constraint\NotInStream;
use Snicco\Component\BetterWPCLI\Testing\Constraint\StatusCode;
use Snicco\Component\BetterWPCLI\Verbosity;

use function array_replace;
use function fopen;
use function fwrite;
use function getenv;
use function is_string;
use function putenv;
use function rewind;
use function stream_get_contents;

use const PHP_EOL;

final class CommandTester
{
    /**
     * @var string
     */
    public const INPUT = 'input';

    /**
     * @var string
     */
    public const INTERACTIVE = 'interactive';

    /**
     * @var string
     */
    public const VERBOSITY = 'verbosity';

    /**
     * @var string
     */
    public const COLORS_STDOUT = 'colors_stdout';

    /**
     * @var string
     */
    public const COLORS_STDERR = 'colors_stderr';

    /**
     * @var Command|Closure():Command
     */
    private $command;

    private ?int $exit_code = null;

    /**
     * @var resource|null
     */
    private $std_out;

    /**
     * @var resource|null
     */
    private $std_err;

    /**
     * @var array{
     *     interactive?: bool,
     *     input?: string[],
     *     verbosity?: int,
     *     colors_stdout?: bool,
     *     colors_stderr?: bool
     * }
     */
    private array $options;

    /**
     * @param Command|Closure():Command $command
     * @param array{
     *     interactive?: bool,
     *     verbosity?: int,
     *     input?: string[],
     *     colors_stdout?: bool,
     *     colors_stderr?: bool
     * } $options
     */
    public function __construct($command, array $options = [])
    {
        $this->command = $command;
        $this->options = $options;
    }

    /**
     * @param string[]                  $positional_args
     * @param array<string,string|bool> $associative_args
     * @param array{
     *     interactive?: bool,
     *     verbosity?: int,
     *     input?: string[],
     *     colors_stdout?: bool,
     *     colors_stderr?: bool
     * } $options
     */
    public function run(array $positional_args = [], array $associative_args = [], array $options = []): void
    {
        $env = getenv('COLUMNS');

        try {
            putenv('COLUMNS=144');

            $this->std_out = $this->getStream('w', 'php://memory');
            $this->std_err = $this->getStream('w', 'php://memory');
            $options = array_replace($this->options, $options);
            $command = $this->getCommand();

            $input = new WPCLIInput(
                $command::synopsis(),
                $positional_args,
                $associative_args,
                // @see CommandTesterTest::that_stdin_stream_is_a_pipe_even_if_no_input_is_passed()
                $this->getStream('r+', 'php://temp', $options['input'] ?? []),
                $options[self::INTERACTIVE] ?? false,
            );

            $verbosity = $options[self::VERBOSITY] ?? Verbosity::NORMAL;
            $colors_stdout = $options[self::COLORS_STDOUT] ?? false;
            $colors_stderr = $options[self::COLORS_STDERR] ?? false;

            $output = new TestOutput(
                $verbosity,
                $colors_stdout,
                new StreamOutput($this->std_out, $verbosity, $colors_stdout),
                new StreamOutput($this->std_err, $verbosity, $colors_stderr)
            );

            $this->exit_code = $command->execute($input, $output);
        } finally {
            if ($env) {
                putenv("COLUMNS={$env}");
            } else {
                putenv('COLUMNS');
            }
        }
    }

    public function assertCommandIsSuccessful(string $message = ''): void
    {
        Assert::assertThat(
            $this->exitCode(),
            new CommandIsSuccessful(),
            $message
        );
    }

    public function assertStatusCode(int $expected, string $message = ''): void
    {
        Assert::assertThat(
            $this->exitCode(),
            new StatusCode($expected),
            $message
        );
    }

    public function seeInStdout(string $expected, string $message = ''): void
    {
        Assert::assertThat(
            $this->getStdout(),
            new InStream($expected, 'stdOut'),
            $message
        );
    }

    public function dontSeeInStdout(string $expected, string $message = ''): void
    {
        Assert::assertThat(
            $this->getStdout(),
            new NotInStream($expected, 'stdOut'),
            $message
        );
    }

    public function seeInStderr(string $expected, string $message = ''): void
    {
        Assert::assertThat(
            $this->getStderr(),
            new InStream($expected, 'stdErr'),
            $message
        );
    }

    public function dontSeeInStderr(string $expected, string $message = ''): void
    {
        Assert::assertThat(
            $this->getStderr(),
            new NotInStream($expected, 'stdErr'),
            $message
        );
    }

    public function getStdout(): string
    {
        if (! $this->std_out) {
            throw CommandTesterException::becauseNoCommandWasRun();
        }

        return $this->getStreamContents($this->std_out);
    }

    public function getStderr(): string
    {
        if (! $this->std_err) {
            throw CommandTesterException::becauseNoCommandWasRun();
        }

        return $this->getStreamContents($this->std_err);
    }

    private function exitCode(): int
    {
        if (null === $this->exit_code) {
            throw CommandTesterException::becauseNoCommandWasRun();
        }

        return $this->exit_code;
    }

    /**
     * @param resource $stream
     */
    private function getStreamContents($stream): string
    {
        rewind($stream);
        $contents = stream_get_contents($stream);

        // @codeCoverageIgnoreStart
        if (! is_string($contents)) {
            throw new RuntimeException('stream_get_contents did not return a string.');
        }
        // @codeCoverageIgnoreEnd

        return $contents;
    }

    /**
     * @param string[] $inputs
     *
     * @return resource
     */
    private function getStream(string $mode, string $type, array $inputs = [])
    {
        $stream = fopen($type, $mode);
        // @codeCoverageIgnoreStart
        if (false === $stream) {
            throw new RuntimeException("Could not open in {$type} stream.");
        }
        // @codeCoverageIgnoreEnd

        foreach ($inputs as $input) {
            // @codeCoverageIgnoreStart
            if (false === fwrite($stream, $input . PHP_EOL)) {
                throw new RuntimeException("Could not write to in {$type} stream.");
            }
            // @codeCoverageIgnoreEnd
        }
        rewind($stream);

        return $stream;
    }

    private function getCommand(): Command
    {
        if ($this->command instanceof Closure) {
            return ($this->command)();
        }

        return $this->command;
    }
}
