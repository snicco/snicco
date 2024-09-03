<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPCLI\Tests\Testing\unit;

use Closure;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;
use Snicco\Component\BetterWPCLI\Testing\CommandTester;
use Snicco\Component\BetterWPCLI\Testing\CommandTesterException;
use Snicco\Component\BetterWPCLI\Tests\Testing\fixtures\ColorsCommand;
use Snicco\Component\BetterWPCLI\Tests\Testing\fixtures\FooCommand;
use Snicco\Component\BetterWPCLI\Tests\Testing\fixtures\PositionalCommand;
use Snicco\Component\BetterWPCLI\Tests\Testing\fixtures\PromptCommand;
use Snicco\Component\BetterWPCLI\Tests\Testing\fixtures\STDINTestCommand;
use Snicco\Component\BetterWPCLI\Tests\Testing\fixtures\VerboseCommand;
use Snicco\Component\BetterWPCLI\Verbosity;

/**
 * @internal
 */
final class CommandTesterTest extends TestCase
{
    /**
     * @test
     */
    public function that_assert_is_successful_can_pass(): void
    {
        $tester = new CommandTester(new FooCommand());
        $tester->run(['foo', 'bar']);
        $tester->assertCommandIsSuccessful();
    }

    /**
     * @test
     */
    public function that_assert_is_successful_can_fail(): void
    {
        $tester = new CommandTester(new FooCommand(11));

        $this->expectFailure(
            "Failed asserting that the command is successful.\nCommand returned exit code 11",
            function () use ($tester) {
                $tester->run(['foo', 'bar']);
                $tester->assertCommandIsSuccessful();
            }
        );
    }

    /**
     * @test
     */
    public function that_assert_status_code_can_pass(): void
    {
        $tester = new CommandTester(new FooCommand(12));
        $tester->run(['foo', 'bar']);
        $tester->assertStatusCode(12);
    }

    /**
     * @test
     */
    public function that_assert_status_code_can_fail(): void
    {
        $tester = new CommandTester(new FooCommand(12));

        $this->expectFailure(
            "Failed asserting that the command returned the status code 10.\nCommand returned exit code 12",
            function () use ($tester) {
                $tester->run(['foo', 'bar']);
                $tester->assertStatusCode(10);
            }
        );
    }

    /**
     * @test
     */
    public function that_see_in_stdout_can_pass(): void
    {
        $tester = new CommandTester(new FooCommand());
        $tester->run(['foo', 'bar']);

        $tester->seeInStdout('foo');
    }

    /**
     * @test
     */
    public function that_see_in_stdout_can_fail(): void
    {
        $tester = new CommandTester(new FooCommand());

        $this->expectFailure(
            "Failed asserting that stdOut contains [foo].\nThe command output was:\nbogus",
            function () use ($tester) {
                $tester->run(['bogus', 'bar']);
                $tester->seeInStdout('foo');
            }
        );
    }

    /**
     * @test
     */
    public function that_see_in_stderr_can_pass(): void
    {
        $tester = new CommandTester(new FooCommand());
        $tester->run(['foo', 'bar']);

        $tester->seeInStderr('bar');
    }

    /**
     * @test
     */
    public function that_see_in_stderr_can_fail(): void
    {
        $tester = new CommandTester(new FooCommand());

        $this->expectFailure(
            "Failed asserting that stdErr contains [foo].\nThe command output was:\n",
            function () use ($tester) {
                $tester->run(['this goes to stdin', 'bogus']);
                $tester->seeInStderr('foo');
            }
        );
    }

    /**
     * @test
     */
    public function that_dont_see_in_stdout_can_pass(): void
    {
        $tester = new CommandTester(new FooCommand());
        $tester->run(['stdOut', 'stdErr']);

        $tester->dontSeeInStdout('foo');
    }

    /**
     * @test
     */
    public function that_dont_see_in_stdout_can_fail(): void
    {
        $tester = new CommandTester(new FooCommand());

        $this->expectFailure(
            "Failed asserting that stdOut does not contain [stdOut].\nThe command output was:",
            function () use ($tester) {
                $tester->run(['stdOut', 'stdErr']);
                $tester->dontSeeInStdout('stdOut');
            }
        );
    }

    /**
     * @test
     */
    public function that_dont_see_in_stderr_can_pass(): void
    {
        $tester = new CommandTester(new FooCommand());
        $tester->run(['stdOut', 'stdErr']);

        $tester->dontSeeInStderr('foo');
    }

    /**
     * @test
     */
    public function that_dont_see_in_stderr_can_fail(): void
    {
        $tester = new CommandTester(new FooCommand());

        $this->expectFailure(
            "Failed asserting that stdErr does not contain [stdErr].\nThe command output was:",
            function () use ($tester) {
                $tester->run(['stdOut', 'stdErr']);
                $tester->dontSeeInStderr('stdErr');
            }
        );
    }

    /**
     * @test
     */
    public function that_stdout_can_be_retrieved(): void
    {
        $tester = new CommandTester(new FooCommand());
        $tester->run(['foo', 'bar']);

        $this->assertStringStartsWith('foo', $tester->getStdout());
    }

    /**
     * @test
     */
    public function that_stderr_can_be_retrieved(): void
    {
        $tester = new CommandTester(new FooCommand());
        $tester->run(['foo', 'bar']);

        $this->assertStringContainsString('[OK] bar', $tester->getStderr());
    }

    /**
     * @test
     */
    public function that_stdout_can_not_be_retrieved_before_running_commands(): void
    {
        $tester = new CommandTester(new FooCommand());

        $this->expectException(CommandTesterException::class);
        $tester->getStdout();
    }

    /**
     * @test
     */
    public function that_stderr_can_not_be_retrieved_before_running_commands(): void
    {
        $tester = new CommandTester(new FooCommand());

        $this->expectException(CommandTesterException::class);
        $tester->getStderr();
    }

    /**
     * @test
     */
    public function that_positional_args_and_options_are_parsed_correctly(): void
    {
        $tester = new CommandTester(new PositionalCommand());

        $tester->run(['FOO', 'BAR', 'BAZ'], [
            'baz' => 'BIZ',
            'biz' => true,
        ]);

        $tester->assertCommandIsSuccessful();
        $tester->seeInStdout('FOOBARBAZBIZ1');
    }

    /**
     * @test
     */
    public function that_a_command_with_prompts_can_be_tested(): void
    {
        $tester = new CommandTester(new PromptCommand(), [
            'input' => [
                'Yes',
                'Yes',
            ],
        ]);

        $tester->run();

        $tester->assertCommandIsSuccessful();

        $tester->seeInStdout('You answered yes once.');
        $tester->seeInStdout('You answered yes twice.');
    }

    /**
     * @test
     */
    public function that_tester_interactivity_can_be_set(): void
    {
        $tester = new CommandTester(new PromptCommand(), [
            CommandTester::INTERACTIVE => false,
        ]);

        $tester->run();

        $tester->assertCommandIsSuccessful();

        $tester->seeInStdout('You answered yes once.');
        $tester->seeInStdout('You answered yes twice.');
    }

    /**
     * @test
     */
    public function that_tester_verbosity_can_be_set(): void
    {
        $tester = new CommandTester(new VerboseCommand());

        $tester->run();
        $tester->assertCommandIsSuccessful();
        $tester->dontSeeInStdout('verbose-only');

        $tester = new CommandTester(new VerboseCommand(), [
            CommandTester::VERBOSITY => Verbosity::VERBOSE,
        ]);

        $tester->run();
        $tester->assertCommandIsSuccessful();
        $tester->seeInStdout('verbose-only');
    }

    /**
     * @test
     */
    public function that_tester_colors_can_be_set(): void
    {
        $tester = new CommandTester(new ColorsCommand(), [
            CommandTester::COLORS_STDOUT => true,
            CommandTester::COLORS_STDERR => true,
        ]);

        $tester->run();
        $tester->assertCommandIsSuccessful();
        $tester->seeInStdout('colors');
        $tester->seeInStderr('colors');

        $tester = new CommandTester(new VerboseCommand(), [
            CommandTester::COLORS_STDOUT => false,
            CommandTester::COLORS_STDERR => false,
        ]);

        $tester->run();
        $tester->assertCommandIsSuccessful();
        $tester->dontSeeInStdout('colors');
        $tester->dontSeeInStderr('colors');
    }

    /**
     * @test
     */
    public function that_the_command_can_be_run_several_times(): void
    {
        $tester = new CommandTester(new FooCommand());
        $tester->run(['foo', 'bar']);

        $tester->seeInStdout('foo');
        $tester->seeInStderr('bar');

        $tester->run(['FOO', 'BAR']);

        $tester->seeInStdout('FOO');
        $tester->seeInStderr('BAR');

        $tester->dontSeeInStdout('foo');
        $tester->dontSeeInStderr('foo');
    }

    /**
     * @test
     */
    public function that_command_options_can_be_passed_per_command(): void
    {
        $tester = new CommandTester(new VerboseCommand(), [
            CommandTester::VERBOSITY => Verbosity::NORMAL,
        ]);

        $tester->run([], [], [
            CommandTester::VERBOSITY => Verbosity::VERBOSE,
        ]);
        $tester->assertCommandIsSuccessful();
        $tester->seeInStdout('verbose-only');
    }

    /**
     * @test
     */
    public function that_the_command_tester_accepts_a_factory(): void
    {
        $count = 0;
        $tester = new CommandTester(function () use (&$count) {
            /**
             * @psalm-suppress UnnecessaryVarAnnotation
             *
             * @var int $count
             */
            ++$count;

            return new FooCommand($count);
        });

        $tester->run(['foo', 'bar']);
        $tester->assertStatusCode(1);

        $tester->run(['foo', 'bar']);
        $tester->assertStatusCode(2);
    }

    /**
     * @test
     */
    public function that_exceptions_are_thrown_for_premature_assertions_of_status_codes(): void
    {
        $this->expectException(CommandTesterException::class);

        $tester = new CommandTester(new VerboseCommand());

        $tester->assertCommandIsSuccessful();
    }

    /**
     * @test
     */
    public function that_stdin_stream_is_a_pipe_if_input_is_passed(): void
    {
        $tester = new CommandTester(new STDINTestCommand(), [
            'input' => ['foo'],
        ]);
        $tester->run();
        $tester->seeInStdout('PIPE');
        $tester->seeInStdout('foo');
    }

    /**
     * @test
     */
    public function that_stdin_stream_is_a_pipe_if_input_empty_input_is_passed(): void
    {
        $tester = new CommandTester(new STDINTestCommand(), [
            'input' => [''],
        ]);
        $tester->run();
        $tester->seeInStdout('PIPE');
    }

    /**
     * @test
     */
    public function that_stdin_stream_is_a_pipe_even_if_no_input_is_passed(): void
    {
        // This is not the desired behavior, but there is no way to make an in-memory stream
        // which will not crash select_stream on PHP8+
        // It should have been a tmp stream from the beginning, they are identical with the
        // only meaningful difference being that tmp will work with stream_select (always returns 1).
        $tester = new CommandTester(new STDINTestCommand(), [
            'input' => [],
        ]);
        $tester->run();
        $tester->seeInStdout('PIPE');
    }

    private function expectFailure(string $message, Closure $test): void
    {
        $fail = false;

        try {
            $test();
            $fail = true;
            Assert::fail('Expected a test failure.');
        } catch (AssertionFailedError $e) {
            if ($fail) {
                throw $e;
            }
            $this->assertStringStartsWith($message, $e->getMessage());
        }
    }
}
