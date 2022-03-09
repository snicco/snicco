<?php

declare(strict_types=1);


namespace Snicco\Component\Session\Driver;

use Snicco\Component\Session\Exception\CouldNotDestroySessions;
use Snicco\Component\Session\ValueObject\SerializedSession;

interface UserSessionsDriver extends SessionDriver
{
    /**
     * @throws CouldNotDestroySessions
     */
    public function destroyAll(): void;

    /**
     * @param int|string $user_id
     *
     * @throws CouldNotDestroySessions
     */
    public function destroyAllForUserId($user_id): void;

    /**
     * @param int|string $user_id
     *
     * @throws CouldNotDestroySessions
     */
    public function destroyAllForUserIdExcept(string $selector, $user_id): void;

    /**
     * @param int|string $user_id
     *
     * @return iterable<string,SerializedSession>
     */
    public function getAllForUserId($user_id): iterable;
}
