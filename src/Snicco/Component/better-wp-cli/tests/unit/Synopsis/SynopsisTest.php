<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPCLI\Tests\unit\Synopsis;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Snicco\Component\BetterWPCLI\Synopsis\InputArgument;
use Snicco\Component\BetterWPCLI\Synopsis\InputFlag;
use Snicco\Component\BetterWPCLI\Synopsis\InputOption;
use Snicco\Component\BetterWPCLI\Synopsis\Synopsis;
use stdClass;

/**
 * @internal
 */
final class SynopsisTest extends TestCase
{
    /**
     * @test
     */
    public function that_all_values_are_returned(): void
    {
        $synopsis = new Synopsis(
            $argument = new InputArgument('foo', 'foo description', ),
            $option = new InputOption('bar', 'bar description'),
            $flag = new InputFlag('baz', 'baz description')
        );

        $array = $synopsis->toArray();

        $this->assertSame([$argument->toArray(), $option->toArray(), $flag->toArray()], $array);
    }

    /**
     * @test
     */
    public function that_two_input_definitions_can_not_have_the_same_name(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Duplicate input name [foo] is not allowed in synopsis.');

        new Synopsis(new InputArgument('foo', 'foo description', ), new InputOption('foo', 'foo description'), );
    }

    /**
     * @test
     */
    public function that_only_one_repeating_position_input_can_be_added(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Positional argument [bar] can not be added after repeating positional argument [foo].'
        );

        new Synopsis(
            new InputArgument('foo', 'foo description', InputArgument::REQUIRED | InputArgument::REPEATING),
            new InputArgument('bar', 'foo description'),
        );
    }

    /**
     * @test
     */
    public function that_optional_arguments_cant_come_before_required_arguments(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Required argument [bar] can not be added after optional argument [foo].');

        new Synopsis(
            new InputArgument('foo', 'foo description', InputArgument::OPTIONAL),
            new InputArgument('bar', 'foo description'),
        );
    }

    /**
     * @test
     */
    public function test_with(): void
    {
        $synopsis = new Synopsis(
            $argument = new InputArgument('foo', 'foo description', ),
            $option = new InputOption('bar', 'bar description'),
            $flag = new InputFlag('baz', 'baz description')
        );

        $new = $synopsis->with($arg2 = new InputArgument('boom', 'boom description'));

        $this->assertSame([$argument->toArray(), $option->toArray(), $flag->toArray()], $synopsis->toArray());

        $this->assertSame([
            $argument->toArray(),
            $option->toArray(),
            $flag->toArray(),
            $arg2->toArray(),
        ], $new->toArray());
    }

    /**
     * @test
     */
    public function that_with_still_validates_everything(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Positional argument [bar] can not be added after repeating positional argument [foo].'
        );

        $synopsis = new Synopsis(
            new InputArgument('foo', 'foo description', InputArgument::REQUIRED | InputArgument::REPEATING),
        );
        $synopsis->with(new InputArgument('bar', 'foo description'));
    }

    /**
     * @test
     */
    public function that_an_invalid_input_throws_an_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('stdClass is not an instance of ');

        $synopsis = new Synopsis(
            new InputArgument('foo', 'foo description', InputArgument::REQUIRED | InputArgument::REPEATING),
        );

        /** @psalm-suppress InvalidArgument */
        $synopsis->with(new stdClass());
    }
}
