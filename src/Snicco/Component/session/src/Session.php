<?php

declare(strict_types=1);

namespace Snicco\Component\Session;

use DateTimeImmutable;
use Snicco\Component\Session\Driver\SessionDriver;
use Snicco\Component\Session\SessionManager\SessionManager;

/**
 * @api
 */
interface Session extends ImmutableSession, MutableSession
{

    /**
     * Release all lifecycle events and clear them afterwards.
     *
     * @return object[]
     * @interal
     */
    public function releaseEvents(): array;

    /**
     * This method is not meant to be used directly by clients.
     * Sessions have to be saved through the {@see SessionManager}
     * After a session has been saved any state changing method has to throw {@see SessionIsLocked}
     *
     * @interal
     */
    public function saveUsing(SessionDriver $driver, DateTimeImmutable $now): void;

}