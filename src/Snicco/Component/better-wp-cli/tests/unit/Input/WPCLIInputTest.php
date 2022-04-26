<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPCLI\Tests\unit\Input;

use BadMethodCallException;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Snicco\Component\BetterWPCLI\Input\WPCLIInput;
use Snicco\Component\BetterWPCLI\Synopsis\InputArgument;
use Snicco\Component\BetterWPCLI\Synopsis\InputFlag;
use Snicco\Component\BetterWPCLI\Synopsis\InputOption;
use Snicco\Component\BetterWPCLI\Synopsis\Synopsis;

use function curl_init;

use const STDIN;

/**
 * @internal
 */
final class WPCLIInputTest extends TestCase
{
    /**
     * @test
     */
    public function test_stream_returns_stdin_by_default(): void
    {
        $input = new WPCLIInput(new Synopsis(),);

        $this->assertSame(STDIN, $input->getStream());
    }

    /**
     * @test
     */
    public function test_exception_if_resource_is_not_a_stream(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('stream');

        /** @psalm-suppress PossiblyFalseArgument */
        new WPCLIInput(new Synopsis(), [], [], curl_init('https://localhost.com'));
    }

    /**
     * @test
     */
    public function test_is_interactive(): void
    {
        $input = new WPCLIInput(new Synopsis(), [], [],);
        $this->assertTrue($input->isInteractive());

        $input = new WPCLIInput(new Synopsis(), [], [], null, false);
        $this->assertFalse($input->isInteractive());
    }

    /**
     * @test
     */
    public function test_exception_if_constructed_with_invalid_positional_args(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Received invalid arguments from wp-cli. Positional arguments should be a list of strings.'
        );
        new WPCLIInput(new Synopsis(), ['foo', 1]);
    }

    /**
     * @test
     */
    public function test_exception_if_positional_args_count_is_greater_than_positional_args_in_synopsis(): void
    {
        $synopsis = new Synopsis(new InputArgument('foo'),);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Received [2] positional arguments from wp-cli but synopsis only has [1] positional argument.'
        );

        new WPCLIInput($synopsis, ['FOO', 'BAR']);
    }

    /**
     * @test
     */
    public function test_no_exception_if_args_count_is_greater_but_a_repeating_argument_is_present(): void
    {
        $synopsis = new Synopsis(
            new InputArgument('foo'),
            new InputArgument('bar', '', InputArgument::REPEATING | InputArgument::REQUIRED)
        );
        $input = new WPCLIInput($synopsis, ['FOO', 'BAR', 'BAZ']);
        $this->assertSame('FOO', $input->getArgument('foo'));
    }

    /**
     * @test
     */
    public function getting_a_named_argument(): void
    {
        $synopsis = $this->getCookbookSynopsis();

        $input = new WPCLIInput(
            $synopsis,
            ['calvin'],
            [
                'type' => 'success',
                'honk' => true,
            ],
        );

        $this->assertSame('calvin', $input->getArgument('name'));
    }

    /**
     * @test
     */
    public function getting_a_named_argument_if_multiple_are_present(): void
    {
        $synopsis = new Synopsis(new InputArgument('foo'), new InputArgument('bar'), new InputArgument('baz'));

        $input = new WPCLIInput($synopsis, ['FOO', 'BAR', 'BAZ'], [],);

        $this->assertSame('FOO', $input->getArgument('foo'));
        $this->assertSame('BAR', $input->getArgument('bar'));
        $this->assertSame('BAZ', $input->getArgument('baz'));
    }

    /**
     * @test
     */
    public function getting_a_named_argument_if_an_optional_value_without_default_is_passed(): void
    {
        $synopsis = new Synopsis(
            new InputArgument('foo'),
            new InputArgument('bar'),
            new InputArgument('baz', '', InputArgument::OPTIONAL)
        );

        $input = new WPCLIInput($synopsis, ['FOO', 'BAR'], [],);

        $this->assertSame('FOO', $input->getArgument('foo'));
        $this->assertSame('BAR', $input->getArgument('bar'));
        $this->assertNull($input->getArgument('baz'));
        $this->assertSame('BAZ_DEFAULT', $input->getArgument('baz', 'BAZ_DEFAULT'));
        $this->assertSame('FOO', $input->getArgument('foo', 'FOO_DEFAULT'));
    }

    /**
     * @test
     */
    public function getting_a_named_argument_with_repeating_arguments(): void
    {
        $synopsis = new Synopsis(
            new InputArgument('foo'),
            new InputArgument('bar', '', InputArgument::REPEATING | InputArgument::REQUIRED),
        );

        $input = new WPCLIInput($synopsis, ['FOO', 'BAR', 'BAZ'], [],);

        $this->assertSame('FOO', $input->getArgument('foo'));
        $this->assertSame(['BAR', 'BAZ'], $input->getRepeatingArgument('bar'));
    }

    /**
     * @test
     */
    public function getting_a_repeating_argument_with_only_one_input(): void
    {
        $synopsis = new Synopsis(
            new InputArgument('foo'),
            new InputArgument('bar', '', InputArgument::REPEATING | InputArgument::REQUIRED),
        );

        $input = new WPCLIInput($synopsis, ['FOO', 'BAR'], [],);

        $this->assertSame('FOO', $input->getArgument('foo'));
        $this->assertSame(['BAR'], $input->getRepeatingArgument('bar'));
    }

    /**
     * @test
     */
    public function test_exception_if_repeating_argument_is_fetched_with_get_argument(): void
    {
        $synopsis = new Synopsis(
            new InputArgument('foo'),
            new InputArgument('bar', '', InputArgument::REPEATING | InputArgument::REQUIRED),
        );

        $input = new WPCLIInput($synopsis, ['FOO', 'BAR', 'BAZ'], [],);

        $this->assertSame('FOO', $input->getArgument('foo'));

        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Positional argument [bar] is repeating.');

        /** @psalm-suppress UnusedMethodCall */
        $input->getArgument('bar');
    }

    /**
     * @test
     */
    public function test_exception_if_argument_is_fetched_with_get_repeating_argument(): void
    {
        $synopsis = new Synopsis(
            new InputArgument('foo'),
            new InputArgument('bar', '', InputArgument::REPEATING | InputArgument::REQUIRED),
        );

        $input = new WPCLIInput($synopsis, ['FOO', 'BAR', 'BAZ'], [],);

        $this->assertSame('FOO', $input->getArgument('foo'));

        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Positional argument [foo] is not repeating.');

        /** @psalm-suppress UnusedMethodCall */
        $input->getRepeatingArgument('foo');
    }

    /**
     * @test
     */
    public function missing_arguments_without_default_values_return_null(): void
    {
        $synopsis = new Synopsis(
            new InputArgument('foo', '', InputArgument::OPTIONAL, 'foo_default'),
            new InputArgument('bar', '', InputArgument::OPTIONAL),
        );

        $input = new WPCLIInput($synopsis, ['foo_default'], [],);

        $this->assertSame('foo_default', $input->getArgument('foo'));
        $this->assertNull($input->getArgument('bar'));
        $this->assertSame('bar_default', $input->getArgument('bar', 'bar_default'));
    }

    /**
     * @test
     */
    public function test_get_option(): void
    {
        $synopsis = new Synopsis(new InputOption('foo'),);

        $input = new WPCLIInput(
            $synopsis,
            [],
            [
                'foo' => 'bar',
                'baz' => 'biz',
            ]
        );

        $this->assertSame('bar', $input->getOption('foo'));
        $this->assertNull($input->getOption('bar'));
        $this->assertSame('bar_default', $input->getOption('bar', 'bar_default'));
        $this->assertSame('biz', $input->getOption('baz'));
    }

    /**
     * @test
     */
    public function test_exception_if_constructed_with_invalid_assoc_value(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Received invalid arguments from wp-cli. Assoc arguments should be all be of type array<string,string|bool>'
        );
        new WPCLIInput(
            new Synopsis(),
            [],
            [
                'foo' => 'bar',
                'baz' => 1,
            ]
        );
    }

    /**
     * @test
     */
    public function test_exception_if_constructed_with_invalid_assoc_key(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Received invalid arguments from wp-cli. Assoc argument keys should not be numerical.'
        );
        new WPCLIInput(
            new Synopsis(),
            [],
            [
                '0' => 'bar',
            ]
        );
    }

    /**
     * @test
     */
    public function test_get_flag(): void
    {
        $synopsis = new Synopsis(new InputOption('foo'),);

        $input = new WPCLIInput(
            $synopsis,
            [],
            [
                'foo' => 'bar',
                'flag' => true,
            ]
        );

        $this->assertSame('bar', $input->getOption('foo'));
        $this->assertTrue($input->getFlag('flag'));
        $this->assertNull($input->getFlag('other-flag'));
        $this->assertFalse($input->getFlag('other-flag', false));
    }

    // https://make.wordpress.org/cli/handbook/guides/commands-cookbook/#wp_cliadd_commands-third-args-parameter
    private function getCookbookSynopsis(): Synopsis
    {
        return new Synopsis(
            new InputArgument('name', 'The name of the person to greet.'),
            new InputOption(
                'type',
                'Whether or not to greet the person with success or error.',
                InputOption::OPTIONAL,
                'success',
                ['success', 'errors']
            ),
            new InputFlag('honk')
        );
    }
}
