<?php

declare(strict_types=1);

namespace Snicco\Component\Session;

use Snicco\Component\Session\ValueObject\SessionId;
use Snicco\Component\Session\ValueObject\CsrfToken;

/**
 * @api
 */
interface ImmutableSession
{
    
    public function id() :SessionId;
    
    /**
     * @return int UNIX timestamp
     */
    public function createdAt() :int;
    
    /**
     * @return int UNIX timestamp
     */
    public function lastRotation() :int;
    
    /**
     * @return int UNIX timestamp
     */
    public function lastActivity() :int;
    
    /**
     * Checks if the given key is in the session and that the value is not NULL.
     */
    public function has(string $key) :bool;
    
    /**
     * Check if the value for the key is truthy. {@see filter_var()}
     */
    public function boolean(string $key, bool $default = false) :bool;
    
    /**
     * Get the previous input of a user, typically during a form submission.
     */
    public function oldInput(string $key = null, $default = null);
    
    public function hasOldInput(string $key = null) :bool;
    
    /**
     * Return all USER-PROVIDED entries in the session.
     *
     * @return array
     */
    public function all() :array;
    
    /**
     * @param  string|string[]  $keys
     */
    public function only($keys) :array;
    
    /**
     * Returns true if all the given keys are not in the session.
     *
     * @param  string|string[]  $keys
     */
    public function missing($keys) :bool;
    
    /**
     * Returns true if all the given keys are in the session.
     *
     * @param  string|string[]  $keys
     */
    public function exists($keys) :bool;
    
    /**
     * Get a value form the session with dot notation.
     * $session->get('user.name', 'calvin')
     *
     * @return mixed
     */
    public function get(string $key, $default = null);
    
    /**
     * Returns a secure, random string that CAN be used to implement csrf protection using the
     * token synchronizer pattern. The token is managed internally and regenerated whenever the
     * session id is rotated/invalidated.
     *
     * @return CsrfToken
     */
    public function csrfToken() :CsrfToken;
    
}