<?php

declare(strict_types=1);

namespace Snicco\Component\Session\Tests\fixtures;

use Snicco\Component\Session\Driver\InMemoryDriver;
use Snicco\Component\Session\Driver\SessionDriver;
use Snicco\Component\Session\EventDispatcher\SessionEventDispatcher;
use Snicco\Component\Session\ReadWriteSession;
use Snicco\Component\Session\Serializer\JsonSerializer;
use Snicco\Component\Session\Session;
use Snicco\Component\Session\SessionManager\SessionManger;
use Snicco\Component\Session\SessionManager\SessionManagerInterface;
use Snicco\Component\Session\ValueObject\SessionConfig;
use Snicco\Component\Session\ValueObject\SessionId;
use Snicco\Component\TestableClock\SystemClock;

use function time;

trait SessionHelpers
{
    public function newSession(?SessionId $id = null, array $data = [], int $now = null): Session
    {
        return new ReadWriteSession($id ?? SessionId::new(), $data, $now ?? time());
    }

    public function getSessionManager(
        SessionConfig $config = null,
        SessionDriver $driver = null,
        SessionEventDispatcher $dispatcher = null
    ): SessionManagerInterface {
        return new SessionManger(
            $config ?? SessionConfig::fromDefaults('sniccowp_test_cookie'),
            $driver ?? new InMemoryDriver(),
            new JsonSerializer(),
            new SystemClock(),
            $dispatcher ?? new class() implements SessionEventDispatcher {
                public function dispatchAll(array $events): void
                {
                }
            },
        );
    }
}
