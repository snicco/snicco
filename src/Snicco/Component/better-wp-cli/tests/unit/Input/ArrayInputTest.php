<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPCLI\Tests\unit\Input;

use PHPUnit\Framework\TestCase;
use Snicco\Component\BetterWPCLI\Input\ArrayInput;
use Snicco\Component\BetterWPCLI\Tests\InMemoryStream;

/**
 * @internal
 */
final class ArrayInputTest extends TestCase
{
    use InMemoryStream;

    /**
     * @test
     */
    public function that_all_methods_work_with_defaults(): void
    {
        $stream = $this->getInMemoryStream();
        $input = new ArrayInput($stream);

        $this->assertTrue($input->isInteractive());
        $this->assertSame($stream, $input->getStream());

        $this->assertNull($input->getArgument('foo_arg'));
        $this->assertSame('default', $input->getArgument('foo_arg', 'default'));

        $this->assertNull($input->getRepeatingArgument('foo_repeating_arg'));
        $this->assertSame(['default'], $input->getRepeatingArgument('foo_repeating_arg', ['default']));

        $this->assertNull($input->getOption('foo_option'));
        $this->assertSame('default', $input->getOption('foo_option', 'default'));

        $this->assertNull($input->getFlag('foo_flag'));
        $this->assertFalse($input->getFlag('foo_flag', false));
    }

    /**
     * @test
     */
    public function that_all_methods_work_with_values(): void
    {
        $stream = $this->getInMemoryStream();
        $input = new ArrayInput(
            $stream,
            true,
            [
                'foo_arg' => 'FOO_ARG',
            ],
            [
                'foo_repeating_arg' => ['FOO_REPEATING_ARG'],
            ],
            [
                'foo_option' => 'FOO_OPTION',
            ],
            [
                'foo_flag' => true,
            ]
        );

        $this->assertTrue($input->isInteractive());
        $this->assertSame($stream, $input->getStream());

        $this->assertSame('FOO_ARG', $input->getArgument('foo_arg'));
        $this->assertSame('FOO_ARG', $input->getArgument('foo_arg', 'default'));

        $this->assertSame(['FOO_REPEATING_ARG'], $input->getRepeatingArgument('foo_repeating_arg'));
        $this->assertSame(['FOO_REPEATING_ARG'], $input->getRepeatingArgument('foo_repeating_arg', ['default']));

        $this->assertSame('FOO_OPTION', $input->getOption('foo_option'));
        $this->assertSame('FOO_OPTION', $input->getOption('foo_option', 'default'));

        $this->assertTrue($input->getFlag('foo_flag'));
        $this->assertTrue($input->getFlag('foo_flag', false));

        $this->assertSame([
            'foo_arg' => 'FOO_ARG',
        ], $input->getArguments());

        $this->assertSame([
            'foo_repeating_arg' => ['FOO_REPEATING_ARG'],
        ], $input->getRepeatingArguments());

        $this->assertSame([
            'foo_option' => 'FOO_OPTION',
        ], $input->getOptions());

        $this->assertSame([
            'foo_flag' => true,
        ], $input->getFlags());
    }
}
