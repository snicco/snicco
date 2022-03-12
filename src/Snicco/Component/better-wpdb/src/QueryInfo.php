<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPDB;

use function array_map;
use function array_shift;
use function is_float;
use function is_int;
use function preg_replace_callback;

/**
 * This class stores information about one executed sql query.
 *
 * @psalm-immutable
 */
final class QueryInfo
{
    public float $start;

    public float $end;

    public float $duration_in_ms;

    /**
     * @var non-empty-string
     */
    public string $sql_with_placeholders;

    /**
     * @var non-empty-string
     */
    public string $sql;

    /**
     * @var array<scalar|null>
     */
    public array $bindings = [];

    /**
     * @param non-empty-string   $sql_with_placeholders
     * @param array<scalar|null> $bindings
     */
    public function __construct(float $start, float $end, string $sql_with_placeholders, array $bindings)
    {
        $this->start = $start;
        $this->end = $end;
        $this->sql_with_placeholders = $sql_with_placeholders;
        $this->bindings = $bindings;

        $this->duration_in_ms = ($end - $start) * 1000.00;

        $this->sql = $this->replacePlaceholders($sql_with_placeholders, $bindings);
    }

    /**
     * @param non-empty-string $sql_with_placeholders
     *
     * @psalm-suppress LessSpecificReturnStatement
     * @psalm-suppress MoreSpecificReturnType
     *
     * @return non-empty-string
     */
    private function replacePlaceholders(string $sql_with_placeholders, array $bindings): string
    {
        $bindings = array_map(function ($binding): string {
            if (is_int($binding)) {
                return (string) $binding;
            }

            if (is_float($binding)) {
                return (string) $binding;
            }

            if (null === $binding) {
                return 'null';
            }

            $binding = (string) $binding;

            return sprintf("'%s'", $binding);
        }, $bindings);

        return (string) preg_replace_callback('#\?#', function () use (&$bindings): string {
            /**
             * @var string[] $bindings
             */
            return (string) (array_shift($bindings));
        }, $sql_with_placeholders);
    }
}
