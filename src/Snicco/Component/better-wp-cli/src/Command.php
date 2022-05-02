<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPCLI;

use ReflectionClass;
use Snicco\Component\BetterWPCLI\Input\Input;
use Snicco\Component\BetterWPCLI\Output\Output;
use Snicco\Component\BetterWPCLI\Synopsis\InputFlag;

use Snicco\Component\BetterWPCLI\Synopsis\Synopsis;

use function str_replace;
use function strtolower;

abstract class Command
{
    // see https://tldp.org/LDP/abs/html/exitcodes.html
    /**
     * @var int
     */
    public const SUCCESS = 0;

    /**
     * @var int
     */
    public const FAILURE = 1;

    /**
     * @var int
     */
    public const INVALID = 2;

    /**
     * @psalm-readonly
     */
    protected static string $short_description = '';

    /**
     * @psalm-readonly
     */
    protected static string $long_description = '';

    /**
     * @psalm-readonly
     */
    protected static string $name = '';

    /**
     * @psalm-readonly
     */
    protected static string $when = 'after_wp_load';

    abstract public function execute(Input $input, Output $output): int;

    public static function synopsis(): Synopsis
    {
        $default = [...self::verbosityFlags(), ...[self::noInteractionFlag()], ...[self::ansiFlag()]];

        return new Synopsis(...$default);
    }

    public static function name(): string
    {
        if (! empty(static::$name)) {
            return static::$name;
        }

        $short_name = (new ReflectionClass(static::class))->getShortName();

        return strtolower(str_replace('Command', '', $short_name));
    }

    public static function when(): string
    {
        return static::$when;
    }

    public static function shortDescription(): string
    {
        return static::$short_description;
    }

    public static function longDescription(): string
    {
        $long = static::$long_description;

        return empty($long) ? static::shortDescription() : $long;
    }

    /**
     * @return list<InputFlag>
     */
    protected static function verbosityFlags(): array
    {
        return [
            new InputFlag('v', 'Verbose output'),
            new InputFlag('vv', 'More verbose output'),
            new InputFlag('vvv', 'Maximum verbosity (equal to --debug)'),
        ];
    }

    protected static function ansiFlag(): InputFlag
    {
        return new InputFlag('ansi', 'Force (or disable --no-ansi) ANSI output.');
    }

    protected static function noInteractionFlag(): InputFlag
    {
        return new InputFlag('interaction', '(--no-interaction) Do not ask any interactive question.');
    }
}
