<?php

declare(strict_types=1);

namespace Snicco\Component\Eloquent\Mysqli;

use mysqli_result;
use RuntimeException;

/**
 * @psalm-internal Snicco\Component\Eloquent
 *
 * @interal
 */
interface MysqliDriverInterface extends PDOAdapter
{

    public function doSelect(string $sql, array $bindings): array;

    public function doStatement(string $sql, array $bindings): bool;

    public function doAffectingStatement(string $sql, array $bindings): int;

    public function doUnprepared(string $sql): bool;

    public function doCursorSelect(string $sql, array $bindings): mysqli_result;

    public function isStillConnected(): bool;

    /**
     * @throws RuntimeException
     */
    public function reconnect(): bool;

}