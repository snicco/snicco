<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPCLI;

use ErrorException;
use LogicException;
use Snicco\Component\BetterWPCLI\CommandLoader\CommandLoader;
use Snicco\Component\BetterWPCLI\Input\Input;
use Snicco\Component\BetterWPCLI\Input\WPCLIInput;
use Snicco\Component\BetterWPCLI\Logger\Logger;
use Snicco\Component\BetterWPCLI\Logger\StdErrLogger;
use Snicco\Component\BetterWPCLI\Output\ConsoleOutput;
use Snicco\Component\BetterWPCLI\Output\ConsoleOutputInterface;
use Snicco\Component\BetterWPCLI\Output\Output;
use Snicco\Component\BetterWPCLI\Style\BG;
use Snicco\Component\BetterWPCLI\Style\SniccoStyle;
use Snicco\Component\BetterWPCLI\Style\Terminal;
use Snicco\Component\BetterWPCLI\Style\Text;
use Snicco\Component\BetterWPCLI\Synopsis\Synopsis;
use Throwable;
use WP_CLI;

use function array_map;
use function array_unshift;
use function basename;
use function function_exists;
use function get_class;
use function is_numeric;
use function max;
use function restore_error_handler;
use function set_error_handler;
use function sprintf;
use function str_pad;
use function str_repeat;
use function str_replace;
use function strlen;
use function trim;

use const E_ALL;

/**
 * @codeCoverageIgnore This can be removed once we have a clarification on: https://github.com/Codeception/Codeception/issues/6450
 */
final class WPCLIApplication
{
    private bool $auto_exit = true;

    private CommandLoader $command_loader;

    private string $name;

    private bool $catch_exceptions = true;

    private int $error_level = E_ALL;

    private bool $commands_registered = false;

    private Logger $logger;

    public function __construct(string $name, CommandLoader $command_loader, Logger $logger = null)
    {
        $this->name = str_replace(' ', '-', $name);
        $this->command_loader = $command_loader;
        $this->logger = $logger ?: new StdErrLogger($name);
    }

    public function catchException(bool $catch_exceptions): void
    {
        $this->catch_exceptions = $catch_exceptions;
    }

    public function throwExceptionsAt(int $error_level): void
    {
        $this->error_level = $error_level;
    }

    public function autoExit(bool $auto_exit): void
    {
        $this->auto_exit = $auto_exit;
    }

    /**
     * @param callable(string, callable, array)|null $add_command
     */
    public function registerCommands(callable $add_command = null): void
    {
        if ($this->commands_registered) {
            throw new LogicException('Commands are already registered.');
        }

        $add_command ??= [WP_CLI::class, 'add_command'];

        foreach ($this->command_loader->commands() as $command_class) {
            $name = $this->prefixedCommandName($command_class);
            $synopsis = $command_class::synopsis();

            $callback = function (array $args, array $assoc_args) use ($synopsis, $name, $command_class): void {
                [$input, $output] = $this->configureIO($name, $synopsis, $args, $assoc_args);

                $this->debug(sprintf('Instantiating command class [%s]', $command_class));

                $command = $this->command_loader->get($command_class);

                $this->runCommand($input, $output, $command);
            };

            ($add_command)($name, $callback, [
                'short_desc' => $command_class::shortDescription(),
                'long_desc' => $command_class::longDescription(),
                'synopsis' => $synopsis->toArray(),
                'when' => $command_class::when(),
            ]);
        }

        $this->commands_registered = true;
    }

    /**
     * @internal
     *
     * @throws Throwable
     *
     * @psalm-internal Snicco\Component\BetterWPCLI
     */
    public function runCommand(Input $input, Output $output, Command $command): int
    {
        $command_name = $this->prefixedCommandName(get_class($command));

        try {
            $this->debug(sprintf('Start running command [%s]', $command_name));

            $exit_code = $this->doRunCommand($command, $input, $output);

            if ($exit_code > 255 || $exit_code < 0) {
                throw new LogicException(
                    sprintf(
                        'Exit code must be between 0 and 255. Command [%s] returned [%s]',
                        $command_name,
                        $exit_code
                    )
                );
            }

            $this->debug(sprintf('Finished command [%s] with exit code [%d]', $command_name, $exit_code));

            if (0 !== $exit_code) {
                /** @var positive-int $exit_code */
                $this->logger->logCommandFailure($exit_code, $command_name, $input);
            }
        } catch (Throwable $e) {
            $this->debug(sprintf('Start handling uncaught exception by command [%s].', $command_name));

            if (! $this->catch_exceptions) {
                $this->debug('Exception handling is disabled. Rethrowing exception.');

                throw $e;
            }

            $this->logger->logError($e, $command_name, $input);

            $this->debug(sprintf('Logged error using [%s].', get_class($this->logger)));

            $this->renderThrowable($e, $input, $output);

            $exit_code = $e->getCode();
            // Some exceptions such as PDO exception return strings.
            if (is_numeric($exit_code)) {
                $exit_code = (int) $exit_code;
                if ($exit_code <= 0) {
                    $exit_code = 1;
                }

                if ($exit_code > 255) {
                    $exit_code = 255;
                }
            } else {
                /** @codeCoverageIgnoreStart */
                $exit_code = 1;
                // @codeCoverageIgnoreEnd
            }
        }

        // @codeCoverageIgnoreStart
        if ($this->auto_exit) {
            // @codeCoverageIgnoreStart
            $this->debug(sprintf('Exiting PHP with exit code [%d].', $exit_code));
            exit($exit_code);
        }

        // @codeCoverageIgnoreEnd

        $this->debug(sprintf('Auto exiting is disabled. Exit code [%d].', $exit_code));

        return $exit_code;
    }

    private function debugGroup(): string
    {
        return sprintf('%s/better-wp-cli', $this->name);
    }

    /**
     * @return array{0: int, 1:string}
     */
    private function determineVerbosity(WPCLIInput $input): array
    {
        $verbosity = Verbosity::NORMAL;
        $verbosity_level = 'normal';

        // @todo Test this when https://github.com/lucatume/wp-browser/issues/576 resolves.
        switch ((int) getenv('SHELL_VERBOSITY')) {
            case -1:
                $verbosity = Verbosity::QUIET;
                $verbosity_level = 'quiet';

                break;
            case 1:
                $verbosity = Verbosity::VERBOSE;
                $verbosity_level = 'verbose';

                break;
            case 2:
                $verbosity = Verbosity::VERY_VERBOSE;
                $verbosity_level = 'verbose';

                break;
            case 3:
                $verbosity = Verbosity::DEBUG;
                $verbosity_level = 'debug';

                break;
        }

        $debug = (bool) WP_CLI::get_config('debug');
        $quiet = (bool) WP_CLI::get_config('quiet');

        if ($debug || $input->getFlag('vvv', false)) {
            $verbosity = Verbosity::DEBUG;
            $verbosity_level = 'debug';
        } elseif ($quiet) {
            $verbosity = Verbosity::QUIET;
            $verbosity_level = 'quiet';
        } elseif ($input->getFlag('vv', false)) {
            $verbosity = Verbosity::VERY_VERBOSE;
            $verbosity_level = 'very verbose';
        } elseif ($input->getFlag('v', false)) {
            $verbosity = Verbosity::VERBOSE;
            $verbosity_level = 'verbose';
        }

        return [$verbosity, $verbosity_level];
    }

    private function isInteractive(array $assoc_args): bool
    {
        if (! isset($assoc_args['interaction'])) {
            return true;
        }

        return true === $assoc_args['interaction'];
    }

    private function isAnsi(WPCLIInput $input): ?bool
    {
        /** @var string|bool $colors */
        $colors = WP_CLI::get_config('color');
        if (false === $colors) {
            return false;
        }

        if (true === $colors) {
            return true;
        }

        return $input->getFlag('ansi');
    }

    private function doRunCommand(Command $command, Input $input, Output $output): int
    {
        set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
            if (($this->error_level & $severity) === 0) {
                return false;
            }

            throw new ErrorException($message, 0, $severity, $file, $line);
        });

        try {
            return $command->execute($input, $output);
        } finally {
            restore_error_handler();
        }
    }

    private function debug(string $message): void
    {
        WP_CLI::debug($message, $this->debugGroup());
    }

    /**
     * @return array{0: Input, 1:Output}
     */
    private function configureIO(string $command_name, Synopsis $synopsis, array $args, array $assoc_args): array
    {
        $this->debug(sprintf('Determining input/output options for command [%s]', $command_name));

        $interactive = $this->isInteractive($assoc_args);

        if (! $interactive) {
            $this->debug(sprintf('Running command [%s] without interaction', $command_name));
        }

        $input = new WPCLIInput($synopsis, $args, $assoc_args, STDIN, $interactive);

        [$verbosity, $verbosity_level] = $this->determineVerbosity($input);
        $this->debug(sprintf('Running command [%s] with verbosity [%s]', $command_name, $verbosity_level));

        $ansi = $this->isAnsi($input);
        if (false === $ansi) {
            $this->debug(sprintf('Running command [%s] without ansi support.', $command_name));
        } elseif (true === $ansi) {
            $this->debug(sprintf('Running command [%s] without forced ansi support.', $command_name));
        } else {
            $this->debug(sprintf('Running command [%s] with auto-detecting ansi support.', $command_name));
        }

        $output = new ConsoleOutput($verbosity, $ansi, $ansi);

        return [$input, $output];
    }

    private function renderThrowable(Throwable $e, Input $input, Output $output): void
    {
        $style = new SniccoStyle($input, $output);

        if ($output instanceof ConsoleOutputInterface) {
            $output = $output->errorOutput();
        }

        do {
            $output->newLine();

            $title = $style->colorize(
                sprintf('In %s line %d:', basename($e->getFile()), $e->getLine()),
                Text::YELLOW
            );
            $output->writeln($title, Verbosity::QUIET);

            $message_block = [];

            $exception_message = trim($e->getMessage());

            if ('' === $exception_message || $output->isVerbose()) {
                $code = (0 !== $e->getCode()) ? ':' . (string) $e->getCode() : '';
                $message_block[] = sprintf('  [%s%s]  ', get_class($e), $code);
            }

            if ('' !== $exception_message) {
                $message_block[] = sprintf('  %s  ', $exception_message);
            }

            /** @var non-empty-list<int> $line_length_array */
            $line_length_array = array_map(fn (string $line): int => $this->stringWidth($line), $message_block);

            $max = max($line_length_array);
            $line_length_message_block = min([$max, Terminal::width()]);

            $empty_red_line = $style->colorize(str_repeat(' ', $line_length_message_block), '', BG::RED);
            $output->writeln($empty_red_line, Verbosity::QUIET);

            foreach ($message_block as $line) {
                $line = str_pad($line, $line_length_message_block, ' ');
                $line = $style->colorize($line, Text::WHITE, BG::RED);
                $output->writeln($line, Verbosity::QUIET);
            }

            $output->writeln($empty_red_line, Verbosity::QUIET);

            if ($output->isVeryVerbose()) {
                $output->newLine();

                $output->writeln($style->colorize('Exception trace:', Text::YELLOW), Verbosity::QUIET);

                $trace = $e->getTrace();
                array_unshift($trace, [
                    'function' => '',
                    'file' => $e->getFile() ?: 'n/a',
                    'line' => $e->getLine() ?: 'n/a',
                    'args' => [],
                ]);

                /**
                 * @var array $single_trace
                 */
                foreach ($trace as $single_trace) {
                    /** @var string $class */
                    $class = $single_trace['class'] ?? '';
                    /** @var string $type */
                    $type = $single_trace['type'] ?? '';
                    /** @var string $function */
                    $function = $single_trace['function'] ?? '';
                    /** @var string $file */
                    $file = $single_trace['file'] ?? 'n/a';
                    /** @var int $line */
                    $line = $single_trace['line'] ?? 'n/a';

                    $output->writeln(
                        sprintf(
                            ' %s%s at %s:%s',
                            $style->colorize($class, Text::GREEN),
                            $style->colorize(('' !== $function) ? $type . $function . '()' : '', Text::GREEN),
                            $file,
                            $line
                        ),
                        Verbosity::QUIET
                    );
                }
            }
        } while ($e = $e->getPrevious());

        $output->newLine();
    }

    private function stringWidth(string $title): int
    {
        // @codeCoverageIgnoreStart
        if (! function_exists('mb_detect_encoding')) {
            return strlen($title);
        }

        if (! function_exists('mb_strwidth')) {
            return strlen($title);
        }

        if (false === $encoding = mb_detect_encoding($title, null, true)) {
            return strlen($title);
        }

        // @codeCoverageIgnoreEnd

        return mb_strwidth($title, $encoding);
    }

    /**
     * @param class-string<Command> $command_class
     */
    private function prefixedCommandName(string $command_class): string
    {
        return $this->name . ' ' . $command_class::name();
    }
}
