<?php

declare(strict_types=1);

namespace Snicco\Component\Eloquent\Mysqli;

use Closure;
use mysqli;
use RuntimeException;

/**
 * @psalm-internal Snicco\Component\Eloquent
 *
 * @interal
 */
final class MysqliReconnect
{
    /**
     * @param Closure(): mysqli $reconnect_callable
     */
    private Closure $reconnect_callable;

    /**
     * @param Closure(): mysqli $reconnect_callable
     */
    public function __construct(Closure $reconnect_callable)
    {
        $this->reconnect_callable = $reconnect_callable;
    }

    /**
     * @throws RuntimeException
     * @psalm-suppress MixedAssignment
     */
    public function getMysqli(): mysqli
    {
        $callable = $this->reconnect_callable;
        $mysqli = $callable();

        if ($mysqli instanceof mysqli) {
            return $mysqli;
        }

        throw new RuntimeException('Cant reconnect with the database.');
    }
}
