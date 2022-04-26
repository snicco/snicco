<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPCLI\Tests\unit\Synopsis;

use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\TestCase;
use Snicco\Component\BetterWPCLI\Synopsis\InputArgument;

/**
 * @internal
 */
final class InputArgumentTest extends TestCase
{
    /**
     * @test
     */
    public function with_basic_configuration(): void
    {
        $argument = new InputArgument('name',);
        $this->assertSame([
            'type' => 'positional',
            'name' => 'name',
            'description' => '',
            'optional' => false,
            'repeating' => false,
        ], $argument->toArray());

        $argument = new InputArgument('name', 'description');
        $this->assertSame([
            'type' => 'positional',
            'name' => 'name',
            'description' => 'description',
            'optional' => false,
            'repeating' => false,
        ], $argument->toArray());
    }

    /**
     * @test
     */
    public function with_optional(): void
    {
        $argument = new InputArgument('name', 'description', InputArgument::OPTIONAL);
        $this->assertSame([
            'type' => 'positional',
            'name' => 'name',
            'description' => 'description',
            'optional' => true,
            'repeating' => false,
        ], $argument->toArray());

        $argument = new InputArgument('name', 'description', InputArgument::REQUIRED);
        $this->assertSame([
            'type' => 'positional',
            'name' => 'name',
            'description' => 'description',
            'optional' => false,
            'repeating' => false,
        ], $argument->toArray());
    }

    /**
     * @test
     */
    public function with_optional_and_required_throws_exception(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Input argument can not be required and optional.');

        new InputArgument('name', 'description', InputArgument::REQUIRED | InputArgument::OPTIONAL,);
    }

    /**
     * @test
     * @psalm-suppress InvalidArgument
     */
    public function with_empty_name_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('name can not be empty');
        new InputArgument('',);
    }

    /**
     * @test
     */
    public function with_optional_and_default(): void
    {
        $argument = new InputArgument('name', 'description', InputArgument::OPTIONAL, 'foo');
        $this->assertSame([
            'type' => 'positional',
            'name' => 'name',
            'description' => 'description',
            'optional' => true,
            'repeating' => false,
            'default' => 'foo',
        ], $argument->toArray());

        $argument = new InputArgument('name', 'description', InputArgument::OPTIONAL, null);
        $this->assertSame([
            'type' => 'positional',
            'name' => 'name',
            'description' => 'description',
            'optional' => true,
            'repeating' => false,
        ], $argument->toArray());
    }

    /**
     * @test
     */
    public function required_with_default_throws_exception(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('A required argument can not have a default value.');

        new InputArgument('name', 'description', InputArgument::REQUIRED, 'foo');
    }

    /**
     * @test
     */
    public function is_repeating_and_required(): void
    {
        $argument = new InputArgument('name', 'description', InputArgument::REPEATING | InputArgument::REQUIRED,);
        $this->assertSame([
            'type' => 'positional',
            'name' => 'name',
            'description' => 'description',
            'optional' => false,
            'repeating' => true,
        ], $argument->toArray());
    }

    /**
     * @test
     */
    public function is_optional_and_repeating(): void
    {
        $argument = new InputArgument('name', 'description', InputArgument::OPTIONAL | InputArgument::REPEATING,);
        $this->assertSame([
            'type' => 'positional',
            'name' => 'name',
            'description' => 'description',
            'optional' => true,
            'repeating' => true,
        ], $argument->toArray());
    }

    /**
     * @test
     */
    public function not_required_and_not_default_throws(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Input argument must be either required or optional');

        new InputArgument('name', 'description', InputArgument::REPEATING,);
    }

    /**
     * @test
     */
    public function is_optional_and_required_and_repeating_throws_exception(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Input argument can not be required and optional.');
        new InputArgument(
            'name',
            'description',
            InputArgument::OPTIONAL | InputArgument::REQUIRED | InputArgument::REPEATING,
        );
    }

    /**
     * @test
     */
    public function invalid_flags_throw_with_to_big_number(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Argument flag [8] is not valid');
        new InputArgument('name', 'description', 8,);
    }

    /**
     * @test
     */
    public function invalid_flags_throw_with_to_small_number(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Argument flag [0] is not valid');
        new InputArgument('name', 'description', 0,);
    }

    /**
     * @test
     */
    public function with_allowed_values(): void
    {
        $argument = new InputArgument('name', 'description', InputArgument::OPTIONAL, null, ['foo', 'bar'],);
        $this->assertSame([
            'type' => 'positional',
            'name' => 'name',
            'description' => 'description',
            'optional' => true,
            'repeating' => false,
            'options' => ['foo', 'bar'],
        ], $argument->toArray());
    }

    /**
     * @test
     * @psalm-suppress InvalidArgument
     */
    public function with_non_string_allowed_values_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('allowed values must be a list of strings.');
        new InputArgument(
            'name',
            'description',
            InputArgument::OPTIONAL,
            null,
            [
                'foo' => 'bar',
            ],
        );
    }

    /**
     * @test
     * @psalm-suppress InvalidArgument
     */
    public function with_empty_allowed_values_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('allowed values must not be empty.');
        new InputArgument('name', 'description', InputArgument::OPTIONAL, null, [],);
    }

    /**
     * @test
     */
    public function with_options_and_default_not_in_options_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Default value [baz] is not in list of allowed values.');
        new InputArgument('name', 'description', InputArgument::OPTIONAL, 'baz', ['foo', 'bar'],);
    }
}
