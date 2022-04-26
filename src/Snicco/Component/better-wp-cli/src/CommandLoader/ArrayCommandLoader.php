<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPCLI\CommandLoader;

use ReflectionClass;
use ReflectionException;
use Snicco\Component\BetterWPCLI\Command;
use Snicco\Component\BetterWPCLI\Exception\CommandNotFound;

use function in_array;

/**
 * @template T of Command
 */
final class ArrayCommandLoader implements CommandLoader
{
    /**
     * @var array<class-string<Command>>
     */
    private array $command_classes;

    /**
     * @var callable(class-string<T>):T
     */
    private $instantiate_command;

    /**
     * @param array<class-string<T>>           $command_classes
     * @param callable(class-string<T>):T|null $instantiate_command
     */
    public function __construct(array $command_classes, callable $instantiate_command = null)
    {
        $this->command_classes = $command_classes;
        $this->instantiate_command = $instantiate_command ?: [$this, 'instantiateCommand'];
    }

    public function get(string $command_class): Command
    {
        if (! in_array($command_class, $this->command_classes, true)) {
            throw CommandNotFound::forClass($command_class);
        }

        return ($this->instantiate_command)($command_class);
    }

    public function commands(): array
    {
        return $this->command_classes;
    }

    /**
     * @internal
     *
     * @param class-string<T> $command_class
     *
     * @throws ReflectionException
     *
     * @return T
     *
     * @psalm-internal Snicco\Component\BetterWPCLI\CommandLoader
     */
    public function instantiateCommand(string $command_class): Command
    {
        return (new ReflectionClass($command_class))->newInstance();
    }
}
