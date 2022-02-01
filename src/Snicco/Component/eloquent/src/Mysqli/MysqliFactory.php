<?php

declare(strict_types=1);

namespace Snicco\Component\Eloquent\Mysqli;

use Closure;
use Snicco\Component\Eloquent\Illuminate\MysqliConnection;
use Snicco\Component\Eloquent\ScopableWP;

/**
 * @internal
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

    private function getReconnect(ScopableWP $wp): Closure
    {
        return function () use ($wp) {
            $success = $wp->wpdb()->check_connection(false);
            if (!$success) {
                return false;
            }
            return $wp->mysqli();
        };
    }

}