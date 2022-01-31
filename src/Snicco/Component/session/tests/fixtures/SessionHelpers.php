<?php

declare(strict_types=1);

namespace Snicco\Component\Session\Tests\fixtures;

use DateTimeImmutable;
use Snicco\Component\Session\Session;
use Snicco\Component\Session\ReadWriteSession;
use Snicco\Component\TestableClock\SystemClock;
use Snicco\Component\Session\Driver\SessionDriver;
use Snicco\Component\Session\ValueObject\SessionId;
use Snicco\Component\Session\Driver\InMemoryDriver;
use Snicco\Component\Session\ValueObject\SessionConfig;
use Snicco\Component\Session\SessionManager\SessionManager;
use Snicco\Component\Session\SessionManager\FactorySessionManager;
use Snicco\Component\Session\EventDispatcher\SessionEventDispatcher;

trait SessionHelpers
{
    
    public function newSession($id = null, array $data = [], DateTimeImmutable $now = null) :Session
    {
        return new ReadWriteSession(
            $id ?? SessionId::createFresh(),
            $data,
            $now ?? new DateTimeImmutable()
        );
    }
    
    public function getSessionManager(
        SessionConfig $config = null,
        SessionDriver $driver = null,
        SessionEventDispatcher $dispatcher = null
    ) :SessionManager {
        return new FactorySessionManager(
            $config ?? SessionConfig::fromDefaults('sniccowp_test_cookie'),
            $driver ?? new InMemoryDriver(),
            new SystemClock(),
            $dispatcher ?? new class implements SessionEventDispatcher
            {
                
                public function dispatchAll(array $events) :void
                {
                    //
                }
                
            },
        );
    }
    
}

