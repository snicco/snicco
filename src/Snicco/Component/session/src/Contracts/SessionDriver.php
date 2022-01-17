<?php

declare(strict_types=1);

namespace Snicco\Session\Contracts;

use DateTimeImmutable;
use Snicco\Session\Exceptions\BadSessionID;
use Snicco\Session\Exceptions\CantDestroySession;
use Snicco\Session\Exceptions\CantReadSessionContent;
use Snicco\Session\Exceptions\CantWriteSessionContent;
use Snicco\Session\ValueObjects\SerializedSessionData;

interface SessionDriver
{
    
    /**
     * Returns the data of the session with the given id.
     * It is NOT required to check if the session can still be considered active.
     *
     * @throws BadSessionID
     * @throws CantReadSessionContent
     */
    public function read(string $session_id) :SerializedSessionData;
    
    /**
     * @throws CantWriteSessionContent
     */
    public function write(string $session_id, SerializedSessionData $data) :void;
    
    /**
     * @throws CantDestroySession
     */
    public function destroy(array $session_ids) :void;
    
    /**
     * @throws CantDestroySession
     */
    public function gc(int $seconds_without_activity) :void;
    
    /**
     * Update the last activity of the session
     *
     * @throws BadSessionID
     */
    public function touch(string $session_id, DateTimeImmutable $now) :void;
    
}