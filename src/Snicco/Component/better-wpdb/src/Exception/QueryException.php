<?php

declare(strict_types=1);


namespace Snicco\Component\BetterWPDB\Exception;

use mysqli_sql_exception;
use RuntimeException;
use Throwable;

use function array_map;
use function implode;
use function is_null;
use function is_string;

class QueryException extends RuntimeException
{

    /**
     * @param array<scalar|null> $bindings
     */
    public function __construct(string $message, string $sql, array $bindings, ?Throwable $prev = null)
    {
        $message .= "\nQuery: [$sql]";

        $bindings = array_map(function ($binding) {
            if (is_null($binding)) {
                return 'null';
            }
            if (!is_string($binding)) {
                return (string)$binding;
            }
            return "'$binding'";
        }, $bindings);

        $message .= "\nBindings: [" . implode(', ', $bindings) . ']';

        parent::__construct($message, ($prev) ? (int)$prev->getCode() : 0, $prev);
    }

    /**
     * @param array<scalar|null> $bindings
     */
    public static function fromMysqliE(string $sql, array $bindings, mysqli_sql_exception $e): self
    {
        return new self($e->getMessage(), $sql, $bindings, $e);
    }
}