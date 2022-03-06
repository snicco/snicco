<?php

declare(strict_types=1);

namespace Snicco\Component\Session;

use Snicco\Component\Session\Driver\SessionDriver;
use Snicco\Component\Session\Serializer\Serializer;
use Snicco\Component\Session\SessionManager\SessionManager;

/**
 * @psalm-internal Snicco\Component\Session
 */
interface Session extends ImmutableSession, MutableSession
{

    /**
     * Release all lifecycle events and clear them afterwards.
     *
     * @return object[]
     */
    public function releaseEvents(): array;

    /**
     * This method is not meant to be used directly by clients.
     * Sessions have to be saved through the {@see SessionManager}
     * After a session has been saved any state changing method has to throw {@see SessionIsLocked}
     */
    public function saveUsing(
        SessionDriver $driver,
        Serializer $serializer,
        string $hashed_validator,
        int $current_timestamp
    ): void;

}