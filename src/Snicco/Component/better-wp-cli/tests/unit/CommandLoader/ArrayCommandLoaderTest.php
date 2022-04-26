<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPCLI\Tests\unit\CommandLoader;

use PHPUnit\Framework\TestCase;
use Snicco\Component\BetterWPCLI\CommandLoader\ArrayCommandLoader;
use Snicco\Component\BetterWPCLI\Exception\CommandNotFound;
use Snicco\Component\BetterWPCLI\Tests\fixtures\Commands\BarCommand;
use Snicco\Component\BetterWPCLI\Tests\fixtures\Commands\FooCommand;

/**
 * @internal
 */
final class ArrayCommandLoaderTest extends TestCase
{
    /**
     * @test
     */
    public function test_commands(): void
    {
        $loader = new ArrayCommandLoader([FooCommand::class, BarCommand::class]);

        $this->assertSame([FooCommand::class, BarCommand::class], $loader->commands());
    }

    /**
     * @test
     */
    public function test_get(): void
    {
        $loader = new ArrayCommandLoader([FooCommand::class, BarCommand::class]);

        $command = $loader->get(FooCommand::class);
        $this->assertInstanceOf(FooCommand::class, $command);

        $command = $loader->get(BarCommand::class);
        $this->assertInstanceOf(BarCommand::class, $command);
    }

    /**
     * @test
     */
    public function test_get_throws_exception_if_loading_a_command_that_was_not_added_in_the_constructor(): void
    {
        $loader = new ArrayCommandLoader([FooCommand::class]);

        $this->expectException(CommandNotFound::class);
        $loader->get(BarCommand::class);
    }
}
