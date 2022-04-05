<?php

declare(strict_types=1);

namespace Snicco\Component\Session\Driver;

use Snicco\Component\Session\Exception\CouldNotDestroySession;
use Snicco\Component\Session\ValueObject\SerializedSession;

interface UserSessionsDriver extends SessionDriver
{
    /**
     * @throws CouldNotDestroySession
     */
    public function destroyAllForAllUsers(): void;

    /**
     * @param int|string $user_id
     *
     * @throws CouldNotDestroySession
     */
    public function destroyAllForUserId($user_id): void;

    /**
     * @param int|string $user_id
     *
     * @throws CouldNotDestroySession
     */
    public function destroyAllForUserIdExcept(string $selector, $user_id): void;

    /**
     * @param int|string $user_id
     *
     * @return iterable<string,SerializedSession> the key is the selector of the session
     */
    public function getAllForUserId($user_id): iterable;
}
