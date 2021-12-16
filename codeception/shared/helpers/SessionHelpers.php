<?php

declare(strict_types=1);

namespace Tests\Codeception\shared\helpers;

use DateTimeImmutable;
use Snicco\Session\Session;
use Snicco\Session\SessionManager;
use Snicco\Session\ValueObjects\SessionId;
use Snicco\Session\Contracts\SessionDriver;
use Snicco\Session\Contracts\SessionInterface;
use Snicco\Session\ValueObjects\SessionConfig;
use Snicco\Session\Drivers\ArraySessionDriver;
use Snicco\Session\Contracts\SessionEventDispatcher;
use Snicco\Session\Contracts\SessionManagerInterface;
use Snicco\Session\ValueObjects\ClockUsingDateTimeImmutable;

trait SessionHelpers
{
    
    public function newSession($id = null, array $data = [], DateTimeImmutable $now = null) :SessionInterface
    {
        return new Session(
            $id ?? SessionId::createFresh(),
            $data,
            $now ?? new DateTimeImmutable()
        );
    }
    
    public function getSessionManager(
        SessionConfig $config = null,
        SessionDriver $driver = null,
        SessionEventDispatcher $dispatcher = null
    ) :SessionManagerInterface {
        return new SessionManager(
            $config ?? SessionConfig::fromDefaults('sniccowp_test_cookie'),
            $driver ?? new ArraySessionDriver(),
            new ClockUsingDateTimeImmutable(),
            $dispatcher ?? new NullSessionEventDispatcher(),
        );
    }
    
}

class NullSessionEventDispatcher implements SessionEventDispatcher
{
    
    public function dispatchAll(array $events) :void
    {
        //
    }
    
}