<?php

declare(strict_types=1);


namespace Snicco\Component\BetterWPDB;

/**
 * @psalm-immutable
 */
final class QueryInfo
{

    public float $start;
    public float $end;
    public string $sql;

    /**
     * @var array<scalar|null>
     */
    public array $bindings;

    /**
     * @param array<scalar|null> $bindings
     */
    public function __construct(float $start, float $end, string $sql, array $bindings)
    {
        $this->start = $start;
        $this->end = $end;
        $this->sql = $sql;
        $this->bindings = $bindings;
    }

}