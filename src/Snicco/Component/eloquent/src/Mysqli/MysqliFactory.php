<?php

declare(strict_types=1);

namespace Snicco\Component\Eloquent\Mysqli;

use Closure;
use mysqli;
use RuntimeException;
use Snicco\Component\Eloquent\Illuminate\MysqliConnection;
use Snicco\Component\Eloquent\ScopableWP;

/**
 * @psalm-internal Snicco\Component\Eloquent
 *
 * @interal
 */
final class MysqliFactory
{

    public function create(): MysqliConnection
    {
        $wp = new ScopableWP();

        $reconnect = new MysqliReconnect($this->getReconnect($wp));

        return new MysqliConnection(
            new MysqliDriver($wp->mysqli(), $reconnect),
            $wp,
        );
    }

    /**
     * @return Closure():mysqli
     */
    private function getReconnect(ScopableWP $wp): Closure
    {
        return function () use ($wp) {
            $success = $wp->wpdb()->check_connection(false);
            if (!$success) {
                throw new RuntimeException('Cant reconnect to wpdb.');
            }
            return $wp->mysqli();
        };
    }

}