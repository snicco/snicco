<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPCLI\Tests\unit\Synopsis;

use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\TestCase;
use Snicco\Component\BetterWPCLI\Synopsis\InputOption;

/**
 * @internal
 */
final class InputOptionTest extends TestCase
{
    /**
     * @test
     */
    public function with_basic_configuration(): void
    {
        $option = new InputOption('name',);
        $this->assertSame([
            'type' => 'assoc',
            'name' => 'name',
            'description' => '',
            'optional' => true,
        ], $option->toArray());

        $option = new InputOption('name', 'description');
        $this->assertSame([
            'type' => 'assoc',
            'name' => 'name',
            'description' => 'description',
            'optional' => true,
        ], $option->toArray());
    }

    /**
     * @test
     */
    public function with_required(): void
    {
        $option = new InputOption('name', 'description', InputOption::REQUIRED);
        $this->assertSame([
            'type' => 'assoc',
            'name' => 'name',
            'description' => 'description',
            'optional' => false,
        ], $option->toArray());
    }

    /**
     * @test
     */
    public function with_optional_and_required_throws_exception(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Input option can not be required and optional.');

        new InputOption('name', 'description', InputOption::REQUIRED | InputOption::OPTIONAL,);
    }

    /**
     * @test
     */
    public function not_required_and_not_default_throws(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Input option must be either required or optional');

        new InputOption('name', 'description', InputOption::COMMA_SEPERATED,);
    }

    /**
     * @test
     */
    public function is_optional_and_required_and_comma_seperated_throws_exception(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Input option can not be required and optional.');
        new InputOption(
            'name',
            'description',
            InputOption::OPTIONAL | InputOption::REQUIRED | InputOption::COMMA_SEPERATED,
        );
    }

    /**
     * @test
     */
    public function invalid_flags_throws_with_to_big_number(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Flag [8] is not valid');
        new InputOption('name', 'description', 8,);
    }

    /**
     * @test
     */
    public function invalid_flags_throws_with_to_small_number(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Flag [0] is not valid');
        new InputOption('name', 'description', 0,);
    }

    /**
     * @test
     */
    public function with_comma_seperated_adds_description(): void
    {
        $option = new InputOption('name', 'description', InputOption::REQUIRED | InputOption::COMMA_SEPERATED);
        $this->assertSame([
            'type' => 'assoc',
            'name' => 'name',
            'description' => 'description (supports multiple comma-seperated values)',
            'optional' => false,
            'repeating' => true,
        ], $option->toArray());
    }

    /**
     * @test
     *
     * @psalm-suppress InvalidArgument
     */
    public function with_empty_name_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('name can not be empty');
        new InputOption('',);
    }

    /**
     * @test
     */
    public function with_optional_and_default(): void
    {
        $option = new InputOption('name', 'description', InputOption::OPTIONAL, 'foo');
        $this->assertSame([
            'type' => 'assoc',
            'name' => 'name',
            'description' => 'description',
            'optional' => true,
            'default' => 'foo',
        ], $option->toArray());

        $option = new InputOption('name', 'description', InputOption::OPTIONAL, null);
        $this->assertSame([
            'type' => 'assoc',
            'name' => 'name',
            'description' => 'description',
            'optional' => true,
        ], $option->toArray());
    }

    /**
     * @test
     */
    public function required_with_default_throws_exception(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('A required argument can not have a default value.');

        new InputOption('name', 'description', InputOption::REQUIRED, 'foo');
    }

    /**
     * @test
     */
    public function is_comma_seperated_and_required(): void
    {
        $option = new InputOption('name', 'description', InputOption::COMMA_SEPERATED | InputOption::REQUIRED,);
        $this->assertSame([
            'type' => 'assoc',
            'name' => 'name',
            'description' => 'description (supports multiple comma-seperated values)',
            'optional' => false,
            'repeating' => true,
        ], $option->toArray());
    }

    /**
     * @test
     */
    public function is_optional_and_comma_separated(): void
    {
        $option = new InputOption('name', 'description', InputOption::OPTIONAL | InputOption::COMMA_SEPERATED,);
        $this->assertSame([
            'type' => 'assoc',
            'name' => 'name',
            'description' => 'description (supports multiple comma-seperated values)',
            'optional' => true,
            'repeating' => true,
        ], $option->toArray());
    }

    /**
     * @test
     */
    public function with_allowed_values(): void
    {
        $option = new InputOption('name', 'description', InputOption::OPTIONAL, null, ['foo', 'bar'],);
        $this->assertSame([
            'type' => 'assoc',
            'name' => 'name',
            'description' => 'description',
            'optional' => true,
            'options' => ['foo', 'bar'],
        ], $option->toArray());
    }

    /**
     * @test
     *
     * @psalm-suppress InvalidArgument
     */
    public function with_non_string_allowed_values_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('allowed values must be a list of strings.');
        new InputOption(
            'name',
            'description',
            InputOption::OPTIONAL,
            null,
            [
                'foo' => 'bar',
            ],
        );
    }

    /**
     * @test
     *
     * @psalm-suppress InvalidArgument
     */
    public function with_empty_allowed_values_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('allowed values must not be empty.');
        new InputOption('name', 'description', InputOption::OPTIONAL, null, [],);
    }

    /**
     * @test
     */
    public function with_options_and_default_not_in_options_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Default value [baz] is not in list of allowed values.');
        new InputOption('name', 'description', InputOption::OPTIONAL, 'baz', ['foo', 'bar'],);
    }
}
