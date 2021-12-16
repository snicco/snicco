<?php

declare(strict_types=1);

namespace Snicco\Session\Contracts;

use DateTimeImmutable;

/**
 * Methods defined on this interface should not be used outside the library itself.
 */
interface SessionInterface extends ImmutableSessionInterface, MutableSessionInterface
{
    
    /**
     * Release all lifecycle events and clear them afterwards.
     *
     * @return object[]
     * @interal
     */
    public function releaseEvents() :array;
    
    /**
     * This method is not meant to be used directly by clients.
     * Sessions have to be saved through the {@see SessionManagerInterface}
     * After a session has been saved any state changing method call has to throw
     * {@see SessionIsLocked}
     *
     * @interal
     */
    public function saveUsing(SessionDriver $driver, DateTimeImmutable $now) :void;
    
}