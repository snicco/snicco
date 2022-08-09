<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPCLI\Tests\unit;

use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use RuntimeException;
use Snicco\Component\BetterWPCLI\CommandLoader\ArrayCommandLoader;
use Snicco\Component\BetterWPCLI\Input\ArrayInput;
use Snicco\Component\BetterWPCLI\Input\Input;
use Snicco\Component\BetterWPCLI\Logger\Logger;
use Snicco\Component\BetterWPCLI\Output\StreamOutput;
use Snicco\Component\BetterWPCLI\Tests\fixtures\Commands\BarCommand;
use Snicco\Component\BetterWPCLI\Tests\fixtures\Commands\ExceptionCommand;
use Snicco\Component\BetterWPCLI\Tests\fixtures\Commands\ExitCodeCommand;
use Snicco\Component\BetterWPCLI\Tests\fixtures\Commands\FooCommand;
use Snicco\Component\BetterWPCLI\Tests\fixtures\Commands\TriggerErrorCommand;
use Snicco\Component\BetterWPCLI\Tests\fixtures\TestOutput;
use Snicco\Component\BetterWPCLI\Tests\InMemoryStream;
use Snicco\Component\BetterWPCLI\Verbosity;
use Snicco\Component\BetterWPCLI\WPCLIApplication;
use Throwable;

use function dirname;
use function file_get_contents;
use function ini_get;
use function ob_get_clean;
use function ob_get_contents;
use function ob_start;
use function rewind;
use function stream_get_contents;
use function trigger_error;
use function trim;

use const E_ALL;
use const E_USER_WARNING;
use const PHP_EOL;

/**
 * @internal
 */
final class WPCLIApplicationTest extends TestCase
{
    use InMemoryStream;

    /**
     * @test
     * @psalm-suppress MixedArrayAccess
     */
    public function test_register_commands(): void
    {
        $application = new WPCLIApplication(
            'snicco',
            new ArrayCommandLoader([FooCommand::class, BarCommand::class])
        );

        /** @var array<string, array{callback: callable, args: array}> $registered_commands */
        $registered_commands = [];

        $add_command = function (string $command_name, callable $command_callback, array $args) use (
            &$registered_commands
        ): void {
            /** @psalm-suppress MixedArrayAssignment */
            $registered_commands[$command_name] = [
                'callback' => $command_callback,
                'args' => $args,
            ];
        };

        $application->registerCommands($add_command);

        $this->assertTrue(
            isset($registered_commands['snicco foo_command_custom']),
            'FooCommand not registered'
        );
        $this->assertTrue(isset($registered_commands['snicco bar']), 'BarCommand not registered');

        $this->assertSame([
            'short_desc' => FooCommand::shortDescription(),
            'long_desc' => FooCommand::longDescription(),
            'synopsis' => FooCommand::synopsis()->toArray(),
            'when' => FooCommand::when(),
        ], $registered_commands['snicco foo_command_custom']['args']);

        $this->assertSame([
            'short_desc' => BarCommand::shortDescription(),
            'long_desc' => BarCommand::longDescription(),
            'synopsis' => BarCommand::synopsis()->toArray(),
            'when' => BarCommand::when(),
        ], $registered_commands['snicco bar']['args']);
    }

    /**
     * @test
     */
    public function that_an_exception_is_thrown_if_commands_are_registered_twice(): void
    {
        $application = new WPCLIApplication(
            'snicco',
            new ArrayCommandLoader([FooCommand::class, BarCommand::class])
        );
        $application->registerCommands();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('already registered.');

        $application->registerCommands();
    }

    /**
     * @test
     */
    public function that_commands_can_be_run(): void
    {
        $application = new WPCLIApplication('snicco', new ArrayCommandLoader([FooCommand::class]));
        $application->autoExit(false);

        $input = new ArrayInput($this->getInMemoryStream());
        $output = new TestOutput();

        $result = $application->runCommand($input, $output, new FooCommand());

        $this->assertSame(0, $result);

        $this->assertSame(['FOO' . PHP_EOL], $output->lines);
    }

    /**
     * @test
     */
    public function that_incorrect_exit_codes_throw_exceptions(): void
    {
        $application = new WPCLIApplication('snicco', new ArrayCommandLoader([]));
        $application->autoExit(false);
        $application->catchException(false);

        try {
            $application->runCommand(
                new ArrayInput($this->getInMemoryStream()),
                new TestOutput(),
                new ExitCodeCommand(-1)
            );

            throw new RuntimeException('No exception thrown');
        } catch (LogicException $e) {
            $this->assertSame(
                'Exit code must be between 0 and 255. Command [snicco exitcode] returned [-1]',
                $e->getMessage()
            );
        }

        try {
            $application->runCommand(
                new ArrayInput($this->getInMemoryStream()),
                new TestOutput(),
                new ExitCodeCommand(400)
            );

            throw new RuntimeException('No exception thrown');
        } catch (LogicException $e) {
            $this->assertSame(
                'Exit code must be between 0 and 255. Command [snicco exitcode] returned [400]',
                $e->getMessage()
            );
        }
    }

    /**
     * @test
     */
    public function that_command_exceptions_are_caught_and_converted_to_the_proper_exit_code(): void
    {
        $application = new WPCLIApplication('snicco', new ArrayCommandLoader([]));
        $application->autoExit(false);

        $exit_code = $application->runCommand(
            new ArrayInput($this->getInMemoryStream()),
            new TestOutput(),
            new ExceptionCommand(function (): void {
                throw new RuntimeException();
            })
        );
        $this->assertSame(1, $exit_code);

        $exit_code = $application->runCommand(
            new ArrayInput($this->getInMemoryStream()),
            new TestOutput(),
            new ExceptionCommand(function (): void {
                throw new RuntimeException('', 16);
            })
        );
        $this->assertSame(16, $exit_code);

        $exit_code = $application->runCommand(
            new ArrayInput($this->getInMemoryStream()),
            new TestOutput(),
            new ExceptionCommand(function (): void {
                throw new RuntimeException('', 0);
            })
        );
        $this->assertSame(1, $exit_code);

        $exit_code = $application->runCommand(
            new ArrayInput($this->getInMemoryStream()),
            new TestOutput(),
            new ExceptionCommand(function (): void {
                throw new RuntimeException('', -1);
            })
        );
        $this->assertSame(1, $exit_code);

        $exit_code = $application->runCommand(
            new ArrayInput($this->getInMemoryStream()),
            new TestOutput(),
            new ExceptionCommand(function (): void {
                throw new RuntimeException('', 255);
            })
        );
        $this->assertSame(255, $exit_code);

        $exit_code = $application->runCommand(
            new ArrayInput($this->getInMemoryStream()),
            new TestOutput(),
            new ExceptionCommand(function (): void {
                throw new RuntimeException('', 300);
            })
        );
        $this->assertSame(255, $exit_code);
    }

    /**
     * @test
     */
    public function that_exception_handling_can_be_disabled(): void
    {
        $application = new WPCLIApplication('snicco', new ArrayCommandLoader([]));
        $application->autoExit(false);
        $application->catchException(false);

        $e = new InvalidArgumentException();

        try {
            $application->runCommand(
                new ArrayInput($this->getInMemoryStream()),
                new TestOutput(),
                new ExceptionCommand(function () use ($e): void {
                    throw $e;
                })
            );

            throw new RuntimeException('Exception handling should have been disabled');
        } catch (InvalidArgumentException $caught) {
            $this->assertSame($e, $caught);
        }
    }

    /**
     * @test
     */
    public function that_errors_are_converted_to_exceptions(): void
    {
        $application = new WPCLIApplication('snicco', new ArrayCommandLoader([]));
        $application->autoExit(false);

        $this->iniSet('display_errors', 'STDOUT');
        ob_start();

        $code = $application->runCommand(
            new ArrayInput($this->getInMemoryStream()),
            new TestOutput(),
            new TriggerErrorCommand(function (): void {
                trigger_error('some warning', E_USER_WARNING);
            })
        );
        $this->assertSame(1, $code);
        $this->assertSame('', ob_get_contents());

        $application->throwExceptionsAt(E_ALL - E_USER_WARNING);

        $code = $application->runCommand(
            new ArrayInput($this->getInMemoryStream()),
            new TestOutput(),
            new TriggerErrorCommand(function (): void {
                trigger_error('some warning', E_USER_WARNING);
            })
        );

        // We need to use output buffering here instead of expectWarning because the error
        // did not go to PHPUnit's test error handler.
        $this->assertSame(0, $code);
        $this->assertStringStartsWith('Warning: some warning', trim((string) ob_get_clean()));
    }

    /**
     * @test
     */
    public function that_error_converting_does_not_apply_globally(): void
    {
        $application = new WPCLIApplication('snicco', new ArrayCommandLoader([]));
        $application->autoExit(false);

        ob_start();

        $code = $application->runCommand(
            new ArrayInput($this->getInMemoryStream()),
            new TestOutput(),
            new TriggerErrorCommand(function (): void {
                trigger_error('some warning', E_USER_WARNING);
            })
        );
        $this->assertSame(1, $code);
        $this->assertSame('', ob_get_clean());

        $this->expectWarning();
        $this->expectWarningMessage('some warning');

        trigger_error('some warning', E_USER_WARNING);
    }

    /**
     * @test
     */
    public function that_exceptions_are_rendered_with_verbosity_normal(): void
    {
        $application = new WPCLIApplication('snicco', new ArrayCommandLoader([]));
        $application->autoExit(false);

        $e = new InvalidArgumentException('foobar');
        $property = new ReflectionProperty($e, 'line');
        $property->setAccessible(true);
        $property->setValue($e, '1');
        $property->setAccessible(false);

        $code = $application->runCommand(
            new ArrayInput($this->getInMemoryStream()),
            new StreamOutput($output_stream = $this->getInMemoryStream(), Verbosity::NORMAL, true),
            new ExceptionCommand(function () use ($e): void {
                throw $e;
            })
        );

        $this->assertSame(1, $code);

        $this->assertSame(
            $this->getFixtureContent(dirname(__DIR__) . '/fixtures/output-colored/exception-verbosity-normal.txt'),
            $this->getStreamContent($output_stream)
        );
    }

    /**
     * @test
     */
    public function that_exceptions_are_rendered_with_verbosity_verbose(): void
    {
        $application = new WPCLIApplication('snicco', new ArrayCommandLoader([]));
        $application->autoExit(false);

        $e = new InvalidArgumentException('foobar');
        $property = new ReflectionProperty($e, 'line');
        $property->setAccessible(true);
        $property->setValue($e, '1');
        $property->setAccessible(false);

        $code = $application->runCommand(
            new ArrayInput($this->getInMemoryStream()),
            new StreamOutput($output_stream = $this->getInMemoryStream(), Verbosity::VERBOSE, true),
            new ExceptionCommand(function () use ($e): void {
                throw $e;
            })
        );

        $this->assertSame(1, $code);

        $this->assertSame(
            $this->getFixtureContent(dirname(__DIR__) . '/fixtures/output-colored/exception-verbosity-verbose.txt'),
            $this->getStreamContent($output_stream)
        );
    }

    /**
     * @test
     */
    public function that_exceptions_are_rendered_with_verbosity_very_verbose(): void
    {
        $application = new WPCLIApplication('snicco', new ArrayCommandLoader([]));
        $application->autoExit(false);

        $e = new InvalidArgumentException('foobar');
        $property = new ReflectionProperty($e, 'line');
        $property->setAccessible(true);
        $property->setValue($e, '1');
        $property->setAccessible(false);

        $code = $application->runCommand(
            new ArrayInput($this->getInMemoryStream()),
            new StreamOutput($output_stream = $this->getInMemoryStream(), Verbosity::VERY_VERBOSE, true),
            new ExceptionCommand(function () use ($e): void {
                throw $e;
            })
        );

        $this->assertSame(1, $code);

        $this->assertStringStartsWith(
            $this->getFixtureContent(
                dirname(__DIR__) . '/fixtures/output-colored/exception-verbosity-very-verbose.txt'
            ),
            $this->getStreamContent($output_stream)
        );
    }

    /**
     * @test
     */
    public function that_failed_commands_are_logged(): void
    {
        $logger = new TestLogger();
        $application = new WPCLIApplication('snicco', new ArrayCommandLoader([]), $logger);
        $application->autoExit(false);

        $code = $application->runCommand(
            $input = new ArrayInput($this->getInMemoryStream()),
            new StreamOutput($this->getInMemoryStream(), Verbosity::VERY_VERBOSE, true),
            new ExitCodeCommand(1)
        );
        $this->assertSame(1, $code);

        $this->assertSame([[1, 'snicco exitcode', $input]], $logger->failures);
        $this->assertCount(0, $logger->errors);

        $code = $application->runCommand(
            new ArrayInput($this->getInMemoryStream()),
            new StreamOutput($this->getInMemoryStream(), Verbosity::VERY_VERBOSE, true),
            new ExitCodeCommand(0)
        );
        $this->assertSame(0, $code);

        $this->assertCount(1, $logger->failures);
        $this->assertCount(0, $logger->errors);
    }

    /**
     * @test
     */
    public function that_uncaught_exceptions_are_logged(): void
    {
        $logger = new TestLogger();
        $application = new WPCLIApplication('snicco', new ArrayCommandLoader([]), $logger);
        $application->autoExit(false);

        $e = new InvalidArgumentException();

        $code = $application->runCommand(
            $input = new ArrayInput($this->getInMemoryStream()),
            new StreamOutput($this->getInMemoryStream(), Verbosity::VERY_VERBOSE, true),
            new ExceptionCommand(function () use ($e): void {
                throw $e;
            })
        );
        $this->assertSame(1, $code);

        $this->assertSame([[$e, 'snicco exception', $input]], $logger->errors);
        $this->assertCount(0, $logger->failures);
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

    private function iniGet(string $key): string
    {
        $res = ini_get($key);
        if (false === $res) {
            throw new RuntimeException("Could not get PHP ini value for {$key}");
        }

        return $res;
    }
}

final class TestLogger implements Logger
{
    public array $errors = [];

    public array $failures = [];

    public function logError(Throwable $e, string $command_name, Input $input): void
    {
        $this->errors[] = [$e, $command_name, $input];
    }

    public function logCommandFailure(int $exit_code, string $command_name, Input $input): void
    {
        $this->failures[] = [$exit_code, $command_name, $input];
    }
}
