<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPCLI\Style;

use Snicco\Component\BetterWPCLI\Exception\CouldNotReadHiddenInput;
use Snicco\Component\BetterWPCLI\Exception\InvalidAnswer;
use Snicco\Component\BetterWPCLI\Exception\MissingInput;
use Snicco\Component\BetterWPCLI\Input\Input;
use Snicco\Component\BetterWPCLI\Output\BufferedOutput;
use Snicco\Component\BetterWPCLI\Output\ConsoleOutputInterface;
use Snicco\Component\BetterWPCLI\Output\Output;
use Snicco\Component\BetterWPCLI\Question\Question;

use function fgets;
use function is_iterable;
use function shell_exec;
use function sprintf;
use function str_repeat;
use function str_replace;
use function strlen;
use function strtolower;
use function substr;
use function substr_count;
use function trim;

use const DIRECTORY_SEPARATOR;
use const PHP_EOL;

/**
 * All helpers methods in this class write to STDERR if an instance of {@see
 * ConsoleOutputInterface} is passed (which is the case by default). You can
 * customize this behaviour by passing false to the second parameter of the
 * constructor. In that case everything will be written exactly to the output
 * that you passed.
 */
final class SniccoStyle
{
    private AnsiColors $colors;

    private int $full_width;

    private BufferedOutput $buffer;

    private Output $output;

    private Input $input;

    private bool $is_windows;

    private bool $stty_available;

    public function __construct(
        Input $input,
        Output $output,
        bool $write_to_stderr_if_available = true,
        bool $is_windows = null,
        bool $tty_available = null
    ) {
        $this->input = $input;
        if ($write_to_stderr_if_available && $output instanceof ConsoleOutputInterface) {
            $this->output = $output->errorOutput();
        } else {
            $this->output = $output;
        }

        $this->colors = new AnsiColors($this->output->supportsDecoration());
        $this->is_windows = $is_windows ?? '\\' === DIRECTORY_SEPARATOR;
        $this->stty_available = $tty_available ?? Terminal::hasSttyAvailable();
        $this->full_width = Terminal::width();
        $this->buffer = new BufferedOutput(DIRECTORY_SEPARATOR === '\\' ? 4 : 2, $output->verbosity(), false);
    }

    public function title(string $message): void
    {
        $this->prependBlock();

        $length = strlen($message);
        $title = $this->colorize($message, Text::YELLOW);
        $underline = $this->colorize(str_repeat('=', $length), Text::YELLOW);

        $this->writeln($title);
        $this->writeln($underline);

        $this->newLine();
    }

    public function section(string $message): void
    {
        $this->prependBlock();

        $length = strlen($message);
        $section_title = $this->colorize($message, Text::YELLOW);
        $section_underline = $this->colorize(str_repeat('-', $length), Text::YELLOW);

        $this->writeln($section_title);
        $this->writeln($section_underline);

        $this->newLine();
    }

    /**
     * This method is meant to be used once to display the final result of
     * executing the given command. Will output black text on green background
     * where the first line is prefixed with "[OK]". If you pass multiple
     * strings they will each be separated by an empty line with green
     * background.
     *
     * @param string|string[] $messages
     */
    public function success($messages): void
    {
        $this->block($this->normalize($messages), '[OK]', BG::GREEN, Text::BLACK);
    }

    /**
     * This method is meant to be used once to display the final result of
     * executing the given command. Will output white text on red background
     * where the first line is prefixed with "[ERROR]". If you pass multiple
     * strings they will each be separated by an empty line with red background.
     *
     * @param string|string[] $messages
     */
    public function error($messages): void
    {
        $this->block($this->normalize($messages), '[ERROR]', BG::RED, Text::WHITE);
    }

    /**
     * This method is meant to be used once to display the final result of
     * executing the given command. Will output black text on yellow background
     * where the first line is prefixed with "[WARNING]". If you pass multiple
     * strings they will each be separated by an empty line with yellow
     * background.
     *
     * @param string|string[] $messages
     */
    public function warning($messages): void
    {
        $this->block($this->normalize($messages), '[WARNING]', BG::YELLOW, Text::BLACK);
    }

    /**
     * Will output yellow text on neutral background where the first line is
     * prefixed with "[NOTE]". If you pass multiple strings they will each be
     * separated by an empty line with neutral background.
     *
     * @param string|string[] $messages
     */
    public function note($messages): void
    {
        $this->block($this->normalize($messages), '[NOTE]', '', Text::YELLOW);
    }

    /**
     * Will output green text on neutral background where the first line is
     * prefixed with "[INFO]". If you pass multiple strings they will each be
     * separated by an empty line with neutral background.
     *
     * @param string|string[] $messages
     */
    public function info($messages): void
    {
        $this->block($this->normalize($messages), '[INFO]', '', Text::GREEN);
    }

    /**
     * Will output neutral text on neutral background without prefix.
     *
     * @param string|string[] $messages
     */
    public function text($messages): void
    {
        $this->prependLF();

        foreach ($this->normalize($messages) as $message) {
            $this->writeln(sprintf(' %s', $message));
        }
    }

    public function colorize(string $message, string $text_color, string $bg_color = ''): string
    {
        return $this->colors->colorize($bg_color . $text_color . $message . Text::RESET);
    }

    public function ask(string $question, string $default = '', bool $hidden = false): string
    {
        return $this->askQuestion((new Question($question, $default))->withHiddenInput($hidden));
    }

    public function askHidden(string $question): string
    {
        return $this->ask($question, '', true);
    }

    public function confirm(string $question, bool $default = true, int $attempts = null): bool
    {
        $default_answer = $default ? 'yes' : 'no';

        $question .= ' (yes/no)';

        $normalizer = fn (string $value): string => strtolower($value);

        $validator = function (string $answer): void {
            if ('yes' === $answer) {
                return;
            }

            if ('no' === $answer) {
                return;
            }

            throw new InvalidAnswer('Answer must be one of [yes/no].');
        };

        return 'yes' === $this->askQuestion(
            new Question($question, $default_answer, $validator, $attempts, $normalizer)
        );
    }

    public function askQuestion(Question $question): string
    {
        $interactive = $this->input->isInteractive();

        if ($interactive) {
            $this->prependBlock();
        }

        $attempts = $question->attempts();
        $invalid_answer = null;

        while (null === $attempts || --$attempts >= 0) {
            if (null !== $invalid_answer) {
                $this->error([$invalid_answer->getMessage(), 'Please try again.']);
            }

            try {
                $answer = $this->doAsk($question);

                $question->validate($answer);

                if ($interactive) {
                    $this->newLine();
                }

                return $answer;
            } catch (InvalidAnswer $e) {
                $invalid_answer = $e;
            }
        }

        /**
         * @var InvalidAnswer $invalid_answer
         */
        throw $invalid_answer;
    }

    /**
     * @param iterable<string> $messages
     */
    private function block(iterable $messages, string $prefix, string $bg_color, string $text_color): void
    {
        $this->prependBlock();

        $empty_bg_line = $this->colorize(str_repeat(' ', $this->full_width), '', $bg_color);

        if ('' !== $bg_color) {
            $this->writeln($empty_bg_line);
        }

        $static_prefix = empty($prefix) ? ' ' : sprintf(' %s ', $prefix);
        $static_prefix_length = strlen($static_prefix);

        $count = 0;
        foreach ($messages as $message) {
            $message_length = strlen($message);

            $line_prefix = (0 === $count)
                ? $static_prefix
                :
                str_repeat(' ', $static_prefix_length);

            $line_prefix_length = strlen($line_prefix);

            $right_padding = str_repeat(' ', $this->full_width - $line_prefix_length - $message_length);

            $line = $this->colorize($line_prefix . $message . $right_padding, $text_color, $bg_color);

            $this->writeln($line);

            if ('' !== $bg_color) {
                $this->writeln($empty_bg_line);
            }

            ++$count;
        }

        $this->newLine();
    }

    /**
     * @param string|string[] $messages
     *
     * @return string[]
     */
    private function normalize($messages): array
    {
        return is_iterable($messages) ? $messages : [$messages];
    }

    /**
     * Calling this method before outputting anything will ensure that there are
     * always 2 LF chars between the previous and next char.
     */
    private function prependBlock(): void
    {
        $buffer = $this->buffer->fetchAndEmpty();
        $buffer = str_replace(PHP_EOL, "\n", $buffer);

        $chars = (string) substr($buffer, -2);

        if ('' === $chars) {
            // The buffer was not written yet, so we need to prepend a LF.
            $this->newLine();

            return;
        }

        // We need to prepend one LF for each non LF character up to a maximum of two.
        $count = 2 - substr_count($chars, "\n");

        $this->newLine($count);
    }

    /**
     * Calling this method before outputting anything will ensure that there is
     * always 1 LF between the previous and next char.
     */
    private function prependLF(): void
    {
        $buffer = $this->buffer->fetchAndEmpty();

        $buffer = str_replace(PHP_EOL, "\n", $buffer);

        $last = (string) substr($buffer, -1);

        if ("\n" !== $last) {
            $this->newLine();
        }
    }

    private function writeln(string $message, int $options = 0): void
    {
        $this->output->writeln($message, $options);
        $this->buffer->writeln($message, $options);
    }

    private function newLine(int $count = 1): void
    {
        $this->output->newLine($count);
        $this->buffer->newLine($count);
    }

    private function doAsk(Question $question): string
    {
        $default = $question->default();

        if (! $this->input->isInteractive()) {
            return $question->normalize($default);
        }

        $prompt = $this->colorize($question->question(), Text::GREEN);
        if ('' !== $default) {
            $prompt .= ' [' . $this->colorize($default, Text::YELLOW) . ']:';
        }

        $this->output->writeln(' ' . $prompt);
        $this->output->write(' > ');

        $answer = $this->doRead($question);

        $answer = trim($answer);
        $answer = strlen($answer) > 0 ? $answer : $default;

        return $question->normalize($answer);
    }

    private function doRead(Question $question): string
    {
        $answer = null;
        if ($question->isHidden()) {
            try {
                $answer = $this->readHiddenInput();
            } catch (CouldNotReadHiddenInput $e) {
                if (! $question->allowsFallbackToVisibleInput()) {
                    throw $e;
                }
            }
        }

        return $answer ?? $this->readStream();
    }

    /**
     * @throws CouldNotReadHiddenInput
     * @psalm-suppress ForbiddenCode
     */
    private function readHiddenInput(): string
    {
        if ($this->is_windows) {
            throw CouldNotReadHiddenInput::becauseOSisWindows();
        }

        if (! $this->stty_available) {
            throw CouldNotReadHiddenInput::becauseSttyIsNotAvailable();
        }

        /** @var string $previous_stty_mode */
        $previous_stty_mode = shell_exec('stty -g');

        shell_exec('stty -echo');

        $answer = $this->readStream();

        shell_exec('stty ' . $previous_stty_mode);

        $this->newLine();

        return $answer;
    }

    /**
     * @throws MissingInput
     */
    private function readStream(): string
    {
        $value = fgets($this->input->getStream(), 4096);

        if (false === $value) {
            throw new MissingInput('Caught ^D during input.');
        }

        return $value;
    }
}
