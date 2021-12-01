<?php

declare(strict_types=1);

namespace Snicco\Session\Contracts;

use stdClass;
use SessionHandlerInterface;

/**
 * NOTE: for all methods that return a session or multiple sessions the driver MUST ONLY
 * validate that the session is not expired absolutely. Session idle/rotation timeouts are
 * handled in the @see SessionManager
 */
interface SessionDriver extends SessionHandlerInterface
{
    
    /**
     * This function takes the session id from the session cookie.
     * The function should return true if the session driver has a valid
     * and NOT expired session for the provided session id.
     * If no session is present for the given id OR the session is expired false should be returned.
     * The session ID is user provided. The driver has to sanitize the input.
     *
     * @param  string  $hashed_id
     *
     * @return bool
     */
    public function isValid(string $hashed_id) :bool;
    
    /**
     * @param  int  $user_id
     *
     * @return array<stdClass> An array of serialized session data as plain objects.
     * The objects MUST contain a "payload" property and an "id" property
     */
    public function getAllByUserId(int $user_id) :array;
    
    /**
     * Destroy all session for the user with the provided id
     * except the the one for the provided token.
     *
     * @param  string  $hashed_token
     * @param  int  $user_id
     */
    public function destroyOthersForUser(string $hashed_token, int $user_id);
    
    /**
     * Destroy all session for the user with the provided id
     *
     * @param  int  $user_id
     */
    public function destroyAllForUser(int $user_id);
    
    /**
     * Destroy all sessions for every user.
     */
    public function destroyAll();
    
}