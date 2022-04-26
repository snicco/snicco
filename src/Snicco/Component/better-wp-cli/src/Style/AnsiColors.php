<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPCLI\Style;

use InvalidArgumentException;

use function array_replace;
use function implode;
use function md5;

/**
 * @see http://graphcomp.com/info/specs/ansi_col.html#colors
 *
 * @internal
 *
 * @psalm-internal Snicco\Component\BetterWPCLI\Style
 */
final class AnsiColors
{
    /**
     * @var array<string, array<string, int>>
     */
    private const COLORS = [
        'color' => [
            'black' => 30,
            'red' => 31,
            'green' => 32,
            'yellow' => 33,
            'blue' => 34,
            'magenta' => 35,
            'cyan' => 36,
            'white' => 37,
        ],
        'style' => [
            'bright' => 1,
            'dim' => 2,
            'underline' => 4,
            'blink' => 5,
            'reverse' => 7,
            'hidden' => 8,
        ],
        'background' => [
            'black' => 40,
            'red' => 41,
            'green' => 42,
            'yellow' => 43,
            'blue' => 44,
            'magenta' => 45,
            'cyan' => 46,
            'white' => 47,
        ],
    ];

    /**
     * @var array<string,array{color?:string, style?: string, background?: string}>
     */
    private const COLOR_MAP = [
        '%y' => [
            'color' => 'yellow',
        ],
        '%g' => [
            'color' => 'green',
        ],
        '%b' => [
            'color' => 'blue',
        ],
        '%r' => [
            'color' => 'red',
        ],
        '%m' => [
            'color' => 'magenta',
        ],
        '%c' => [
            'color' => 'cyan',
        ],
        '%w' => [
            'color' => 'white',
        ],
        '%k' => [
            'color' => 'black',
        ],
        '%n' => [
            'color' => 'reset',
        ],
        '%Y' => [
            'color' => 'yellow',
            'style' => 'bright',
        ],
        '%G' => [
            'color' => 'green',
            'style' => 'bright',
        ],
        '%B' => [
            'color' => 'blue',
            'style' => 'bright',
        ],
        '%R' => [
            'color' => 'red',
            'style' => 'bright',
        ],
        '%M' => [
            'color' => 'magenta',
            'style' => 'bright',
        ],
        '%C' => [
            'color' => 'cyan',
            'style' => 'bright',
        ],
        '%W' => [
            'color' => 'white',
            'style' => 'bright',
        ],
        '%K' => [
            'color' => 'black',
            'style' => 'bright',
        ],
        '%N' => [
            'color' => 'reset',
            'style' => 'bright',
        ],
        '%3' => [
            'background' => 'yellow',
        ],
        '%2' => [
            'background' => 'green',
        ],
        '%4' => [
            'background' => 'blue',
        ],
        '%1' => [
            'background' => 'red',
        ],
        '%5' => [
            'background' => 'magenta',
        ],
        '%6' => [
            'background' => 'cyan',
        ],
        '%7' => [
            'background' => 'white',
        ],
        '%0' => [
            'background' => 'black',
        ],
        '%F' => [
            'style' => 'blink',
        ],
        '%U' => [
            'style' => 'underline',
        ],
        '%8' => [
            'style' => 'reverse',
        ],
        '%9' => [
            'style' => 'bright',
        ],
    ];

    private bool $colorize;

    /**
     * @var array<string,string>
     */
    private array $cache = [];

    public function __construct(bool $colorize = true)
    {
        $this->colorize = $colorize;
    }

    public function colorize(string $string): string
    {
        $passed = $string;

        $cache_key = md5($passed);
        if (isset($this->cache[$cache_key])) {
            return $this->cache[$cache_key];
        }

        if (! $this->colorize) {
            $return = str_replace(array_keys(self::COLOR_MAP), '', $string);
            $this->cache[$cache_key] = $return;

            return $return;
        }

        $string = str_replace('%%', '%¾', $string);

        foreach (self::COLOR_MAP as $key => $color_definition) {
            $string = str_replace($key, $this->colorToAnsi($color_definition), $string);
        }

        $string = str_replace('%¾', '%', $string);
        $this->cache[$cache_key] = $string;

        return $string;
    }

    /**
     * @param array{color?:string, style?: string, background?: string} $color_definition
     */
    private function colorToAnsi(array $color_definition): string
    {
        $color_definition = array_replace([
            'color' => null,
            'style' => null,
            'background' => null,
        ], $color_definition);

        if ('reset' === $color_definition['color']) {
            return "\033[0m";
        }

        $colors = [];

        foreach (['color', 'style', 'background'] as $type) {
            if (null === $color_definition[$type]) {
                continue;
            }

            $code = $color_definition[$type];

            if (! isset(self::COLORS[$type][$code])) {
                // @codeCoverageIgnoreStart
                throw new InvalidArgumentException('Invalid color code. This should not happen.');
                // @codeCoverageIgnoreEnd
            }

            $colors[] = self::COLORS[$type][$code];
        }

        return "\033[" . implode('', $colors) . 'm';
    }
}
