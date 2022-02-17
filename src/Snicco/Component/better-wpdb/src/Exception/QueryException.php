<?php

declare(strict_types=1);


namespace Snicco\Component\BetterWPDB\Exception;

use RuntimeException;
use Throwable;

class QueryException extends RuntimeException
{
    private string $sql;
    private array $bindings;

    public function __construct(string $sql, array $bindings, ?Throwable $prev = null)
    {
        parent::__construct('', ($prev) ? (int)$prev->getCode() : 0, $prev);
        $this->sql = $sql;
        $this->bindings = $bindings;
    }
}