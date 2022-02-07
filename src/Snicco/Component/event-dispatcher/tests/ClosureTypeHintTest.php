<?php

declare(strict_types=1);


namespace Snicco\Component\EventDispatcher\Tests;

use PHPUnit\Framework\TestCase;
use Snicco\Component\EventDispatcher\ClosureTypeHint;
use Snicco\Component\EventDispatcher\Exception\InvalidListener;
use stdClass;

/**
 * @psalm-suppress UnusedClosureParam
 * @psalm-suppress MissingClosureParamType
 */
final class ClosureTypeHintTest extends TestCase
{

    /**
     * @test
     */
    public function test_first(): void
    {
        $closure = function (stdClass $foo, string $bar): void {
        };

        $this->assertSame(stdClass::class, ClosureTypeHint::first($closure));
    }

    /**
     * @test
     */
    public function test_exception_for_no_arguments(): void
    {
        $this->expectException(InvalidListener::class);
        $this->expectExceptionMessage('A closure listener must have a type hinted object as the first parameter.');

        $closure = function (): void {
        };

        ClosureTypeHint::first($closure);
    }

    /**
     * @test
     */
    public function test_exception_for_no_type_hinted_first_argument(): void
    {
        $this->expectException(InvalidListener::class);
        $this->expectExceptionMessage('A closure listener must have a type hinted object as the first parameter.');

        $closure = function ($foo): void {
        };

        ClosureTypeHint::first($closure);
    }


}