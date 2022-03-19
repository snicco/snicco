<?php

declare(strict_types=1);

namespace Snicco\Component\Session\Driver;

use Snicco\Component\Session\Exception\CouldNotDestroySession;
use Snicco\Component\Session\Exception\CouldNotReadSessionContent;
use Snicco\Component\Session\Exception\CouldNotWriteSessionContent;
use Snicco\Component\Session\Exception\UnknownSessionSelector;
use Snicco\Component\Session\ValueObject\SerializedSession;

interface SessionDriver
{
    /**
     * Returns the data of the session with the given selector. It is NOT
     * required to check if the session can still be considered active.
     *
     * @throws UnknownSessionSelector
     * @throws CouldNotReadSessionContent
     */
    public function read(string $selector): SerializedSession;

    /**
     * @throws CouldNotWriteSessionContent
     */
    public function write(string $selector, SerializedSession $session): void;

    /**
     * @throws CouldNotDestroySession
     */
    public function destroy(string $selector): void;

    /**
     * @throws CouldNotDestroySession
     */
    public function gc(int $seconds_without_activity): void;

    /**
     * Update the last activity of the session.
     *
     * @throws UnknownSessionSelector
     * @throws CouldNotWriteSessionContent
     */
    public function touch(string $selector, int $current_timestamp): void;
}
