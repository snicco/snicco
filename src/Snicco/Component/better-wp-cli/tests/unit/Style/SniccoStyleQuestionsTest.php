<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPCLI\Tests\unit\Style;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Snicco\Component\BetterWPCLI\Exception\CouldNotReadHiddenInput;
use Snicco\Component\BetterWPCLI\Exception\InvalidAnswer;
use Snicco\Component\BetterWPCLI\Exception\MissingInput;
use Snicco\Component\BetterWPCLI\Input\Input;
use Snicco\Component\BetterWPCLI\Question\Question;
use Snicco\Component\BetterWPCLI\Style\SniccoStyle;
use Snicco\Component\BetterWPCLI\Tests\fixtures\TestOutput;

use function implode;
use function strpos;
use function strtoupper;

use const PHP_EOL;

/**
 * @internal
 */
final class SniccoStyleQuestionsTest extends TestCase
{
    /**
     * @test
     */
    public function test_ask(): void
    {
        $input = $this->getInputStream(['', '8AM']);
        $output = new TestOutput();

        $style = new SniccoStyle($this->createStreamableInputInterfaceMock($input), $output);

        $answer = $style->ask('What time is it?', '2AM');
        $this->assertSame('2AM', $answer);
        $this->assertSame([PHP_EOL, ' What time is it? [2AM]:' . PHP_EOL, ' > ', PHP_EOL], $output->lines);

        $answer = $style->ask('What time is it?', '2AM');
        $this->assertSame('8AM', $answer);
    }

    /**
     * @test
     */
    public function test_ask_without_default_value(): void
    {
        $input = $this->getInputStream(["\n"]);
        $output = new TestOutput();

        $style = new SniccoStyle($this->createStreamableInputInterfaceMock($input), $output);

        $answer = $style->ask('What time is it?');

        $this->assertSame([PHP_EOL, ' What time is it?' . PHP_EOL, ' > ', PHP_EOL], $output->lines);
        $this->assertSame('', $answer);
    }

    /**
     * @test
     */
    public function test_ask_with_invalid_input_throws_exception(): void
    {
        $input = $this->getInputStream([]);
        $output = new TestOutput();

        $style = new SniccoStyle($this->createStreamableInputInterfaceMock($input), $output);

        $this->expectException(MissingInput::class);
        $style->ask('What time is it?');
    }

    /**
     * @test
     */
    public function that_asking_in_non_interactive_mode_returns_the_default_answer(): void
    {
        $input = $this->getInputStream(["\n"]);
        $output = new TestOutput();

        $style = new SniccoStyle($this->createStreamableInputInterfaceMock($input, false), $output);

        $answer = $style->ask('What time is it?', '2AM');

        $this->assertSame('2AM', $answer);
        $this->assertSame([], $output->lines);
    }

    /**
     * @test
     */
    public function that_ask_hidden_works_with_stty_available_on_non_windows(): void
    {
        $input = $this->getInputStream(['password']);
        $output = new TestOutput();

        $style = new SniccoStyle($this->createStreamableInputInterfaceMock($input), $output, true, false, true);

        $answer = $style->askHidden('What is your password?');
        $this->assertSame([PHP_EOL, ' What is your password?' . PHP_EOL, ' > ', PHP_EOL, PHP_EOL], $output->lines);
        $this->assertSame('password', $answer);

        $input = $this->getInputStream(['password']);
        $output = new TestOutput();

        $style = new SniccoStyle($this->createStreamableInputInterfaceMock($input), $output, true, false, true);

        $answer = $style->ask('What is your password?', '', true);
        $this->assertSame([PHP_EOL, ' What is your password?' . PHP_EOL, ' > ', PHP_EOL, PHP_EOL], $output->lines);
        $this->assertSame('password', $answer);
    }

    /**
     * @test
     */
    public function that_ask_hidden_throws_an_exception_on_unix_if_stty_not_available(): void
    {
        $input = $this->getInputStream(['password']);
        $output = new TestOutput();

        $style = new SniccoStyle($this->createStreamableInputInterfaceMock($input), $output, true, false, false);

        $this->expectException(CouldNotReadHiddenInput::class);
        $this->expectDeprecationMessage('stty');

        $style->askHidden('What is your password?');
    }

    /**
     * @test
     */
    public function that_ask_hidden_throws_an_exception_on_windows(): void
    {
        $input = $this->getInputStream(['password']);
        $output = new TestOutput();

        $style = new SniccoStyle($this->createStreamableInputInterfaceMock($input), $output, true, true,);

        $this->expectException(CouldNotReadHiddenInput::class);
        $this->expectDeprecationMessage('Windows');

        $style->askHidden('What is your password?');
    }

    /**
     * @test
     */
    public function that_a_question_can_explicitly_allow_hidden_input_to_be_visible(): void
    {
        $input = $this->getInputStream(['password']);
        $output = new TestOutput();

        $style = new SniccoStyle($this->createStreamableInputInterfaceMock($input), $output, true, true,);

        $question = new Question('What is your password?');

        $answer = $style->askQuestion($question->withFallbackVisibleInput());

        $this->assertSame([
            PHP_EOL,
            ' What is your password?' . PHP_EOL,
            ' > ',
            PHP_EOL,
            // No new line here because stty did not run.
        ], $output->lines);
        $this->assertSame('password', $answer);
    }

    /**
     * @test
     */
    public function that_confirm_works(): void
    {
        $input = $this->getInputStream(['', '', 'no', 'Yes']);

        $output = new TestOutput();
        $style = new SniccoStyle($this->createStreamableInputInterfaceMock($input), $output);

        $answer = $style->confirm('Do you want to proceed?');
        $this->assertTrue($answer);
        $this->assertSame([
            PHP_EOL,
            ' Do you want to proceed? (yes/no) [yes]:' . PHP_EOL,
            ' > ',
            PHP_EOL,
        ], $output->lines);

        $output = new TestOutput();
        $style = new SniccoStyle($this->createStreamableInputInterfaceMock($input), $output);

        $answer = $style->confirm('Do you want to proceed?', false);
        $this->assertFalse($answer);
        $this->assertSame([
            PHP_EOL,
            ' Do you want to proceed? (yes/no) [no]:' . PHP_EOL,
            ' > ',
            PHP_EOL,
        ], $output->lines);

        $output = new TestOutput();
        $style = new SniccoStyle($this->createStreamableInputInterfaceMock($input), $output);

        $answer = $style->confirm('Do you want to proceed?');
        $this->assertSame([
            PHP_EOL,
            ' Do you want to proceed? (yes/no) [yes]:' . PHP_EOL,
            ' > ',
            PHP_EOL,
        ], $output->lines);

        $this->assertFalse($answer);

        $output = new TestOutput();
        $style = new SniccoStyle($this->createStreamableInputInterfaceMock($input), $output);

        $answer = $style->confirm('Do you want to proceed?', false);
        $this->assertSame([
            PHP_EOL,
            ' Do you want to proceed? (yes/no) [no]:' . PHP_EOL,
            ' > ',
            PHP_EOL,
        ], $output->lines);

        $this->assertTrue($answer);
    }

    /**
     * @test
     */
    public function that_confirm_throws_exceptions_for_invalid_responses(): void
    {
        $input = $this->getInputStream(['bogus', 'bogus']);

        $output = new TestOutput();
        $style = new SniccoStyle($this->createStreamableInputInterfaceMock($input), $output);

        try {
            $style->confirm('Do you want to proceed?', true, 2);

            throw new RuntimeException('Exception should have been thrown');
        } catch (InvalidAnswer $e) {
            $this->assertStringContainsString('one of [yes/no]', $e->getMessage());
        }

        $question_count = 0;
        $error_count = 0;

        foreach ($output->lines as $line) {
            if (false !== strpos($line, 'Do you want to proceed?')) {
                ++$question_count;
            }

            if (false !== strpos($line, '[ERROR]')) {
                ++$error_count;
            }
        }

        $this->assertSame(2, $question_count);
        $this->assertSame(1, $error_count);
    }

    /**
     * @test
     */
    public function that_normalizers_are_run_before_validators(): void
    {
        $input = $this->getInputStream(['foo', 'bar']);
        $output = new TestOutput();

        $style = new SniccoStyle($this->createStreamableInputInterfaceMock($input), $output);

        $normalizer = fn (string $string): string => strtoupper($string);
        $validator = function (string $answer): void {
            if ('FOO' !== $answer) {
                throw new InvalidAnswer('MUST BE FOO');
            }
        };
        $question = new Question('Foo or bar', '', $validator, 1, $normalizer);

        $this->assertSame('FOO', $style->askQuestion($question));

        $this->expectExceptionMessage('MUST BE FOO');
        $style->askQuestion($question);
    }

    /**
     * @param string[] $input_lines
     *
     * @return resource
     */
    private function getInputStream(array $input_lines)
    {
        $input = implode(PHP_EOL, $input_lines);
        $stream = fopen('php://memory', 'r+', false);

        if (false === $stream) {
            throw new RuntimeException('Could not open in memory stream');
        }

        fwrite($stream, $input);
        rewind($stream);

        return $stream;
    }

    /**
     * @param resource|null $stream
     */
    private function createStreamableInputInterfaceMock($stream = null, bool $interactive = true): Input
    {
        $mock = $this->createMock(Input::class);
        $mock
            ->method('isInteractive')
            ->willReturn($interactive);

        if (null !== $stream) {
            $mock
                ->method('getStream')
                ->willReturn($stream);
        }

        return $mock;
    }
}
