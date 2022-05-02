<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPCLI\Tests\unit;

use PHPUnit\Framework\TestCase;
use Snicco\Component\BetterWPCLI\Command;
use Snicco\Component\BetterWPCLI\Input\Input;
use Snicco\Component\BetterWPCLI\Output\Output;
use Snicco\Component\BetterWPCLI\Synopsis\InputArgument;
use Snicco\Component\BetterWPCLI\Synopsis\Synopsis;
use Snicco\Component\BetterWPCLI\Tests\fixtures\Commands\BarCommand;
use Snicco\Component\BetterWPCLI\Tests\fixtures\Commands\FooCommand;

/**
 * @internal
 */
final class CommandTest extends TestCase
{
    /**
     * @test
     */
    public function that_the_configuration_works(): void
    {
        $name = FooCommand::name();
        $this->assertSame('foo_command_custom', $name);

        $this->assertSame('', FooCommand::shortDescription());
        $this->assertSame('long', FooCommand::longDescription());
        $this->assertSame('after_wp_load', FooCommand::when());

        $this->assertEquals(
            new Synopsis(
                new InputArgument('foo', 'foo description', InputArgument::REQUIRED),
                new InputArgument('bar', 'bar description', InputArgument::REQUIRED)
            ),
            FooCommand::synopsis()
        );

        $this->assertSame('bar', BarCommand::name());
        $this->assertSame('This is the bar command', BarCommand::shortDescription());
        $this->assertSame('This is the bar command', BarCommand::longDescription());
    }

    /**
     * @test
     */
    public function that_the_synopsis_has_all_default_flags(): void
    {
        $synopsis = Command::synopsis()->toArray();
        $this->assertSame([
            [
                'type' => 'flag',
                'name' => 'v',
                'description' => 'Verbose output',
                'optional' => true,
            ],
            [
                'type' => 'flag',
                'name' => 'vv',
                'description' => 'More verbose output',
                'optional' => true,
            ],
            [
                'type' => 'flag',
                'name' => 'vvv',
                'description' => 'Maximum verbosity (equal to --debug)',
                'optional' => true,
            ],
            [
                'type' => 'flag',
                'name' => 'interaction',
                'description' => '(--no-interaction) Do not ask any interactive question.',
                'optional' => true,
            ],
            [
                'type' => 'flag',
                'name' => 'ansi',
                'description' => 'Force (or disable --no-ansi) ANSI output.',
                'optional' => true,
            ],
        ], $synopsis);
    }

    /**
     * @test
     */
    public function that_verbosity_flags_are_correct(): void
    {
        $this->assertSame([
            [
                'type' => 'flag',
                'name' => 'v',
                'description' => 'Verbose output',
                'optional' => true,
            ],
            [
                'type' => 'flag',
                'name' => 'vv',
                'description' => 'More verbose output',
                'optional' => true,
            ],
            [
                'type' => 'flag',
                'name' => 'vvv',
                'description' => 'Maximum verbosity (equal to --debug)',
                'optional' => true,
            ],
        ], CommandWithVerbosityFlags::synopsis()->toArray());
    }

    /**
     * @test
     */
    public function that_flags_are_correct(): void
    {
        $this->assertSame([
            [
                'type' => 'flag',
                'name' => 'ansi',
                'description' => 'Force (or disable --no-ansi) ANSI output.',
                'optional' => true,
            ],
            [
                'type' => 'flag',
                'name' => 'interaction',
                'description' => '(--no-interaction) Do not ask any interactive question.',
                'optional' => true,
            ],
        ], CommandWithFlags::synopsis()->toArray());
    }
}

final class CommandWithVerbosityFlags extends Command
{
    public function execute(Input $input, Output $output): int
    {
        return 0;
    }

    public static function synopsis(): Synopsis
    {
        return (new Synopsis())->with(self::verbosityFlags());
    }
}

final class CommandWithFlags extends Command
{
    public function execute(Input $input, Output $output): int
    {
        return 0;
    }

    public static function synopsis(): Synopsis
    {
        return (new Synopsis())->with(self::ansiFlag())
            ->with(self::noInteractionFlag());
    }
}
