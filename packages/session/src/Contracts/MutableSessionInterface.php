<?php

declare(strict_types=1);

namespace Snicco\Session\Contracts;

use Closure;
use Snicco\Session\MessageBag;
use Snicco\Session\Exceptions\SessionIsLocked;

/**
 * To guarantee the integrity of the session, all methods defined on this interface will throw an
 * exception if a session is locked for modification. (Typically after saving it.)
 *
 * @api
 */
interface MutableSessionInterface
{
    
    /**
     * Invalidate the session entirely. All data will be flushed.
     *
     * @throws SessionIsLocked
     */
    public function invalidate() :void;
    
    /**
     * Invalidates the session id and keeps the current data.
     *
     * @throws SessionIsLocked
     */
    public function rotate() :void;
    
    /**
     * @param  string|string[]  $keys
     *
     * @throws SessionIsLocked
     */
    public function forget($keys) :void;
    
    /**
     * @param  array|string  $key
     * @param  mixed  $value  Only used if key is string
     *
     * @throws SessionIsLocked
     */
    public function put($key, $value = null) :void;
    
    /**
     * @param  array<string,mixed>  $attributes
     *
     * @throws SessionIsLocked
     */
    public function replace(array $attributes) :void;
    
    /**
     * @throws SessionIsLocked
     */
    public function putIfMissing(string $key, Closure $callback) :void;
    
    /**
     * @throws SessionIsLocked
     */
    public function decrement(string $key, $amount = 1) :void;
    
    /**
     * @throws SessionIsLocked
     */
    public function increment(string $key, int $amount = 1, int $start_value = 0) :void;
    
    /**
     * @param  mixed  $value
     *
     * @throws SessionIsLocked
     */
    public function push(string $key, $value) :void;
    
    /**
     * Flash messages into the session. The data will be removed after saving the session twice.
     *
     * @param  mixed  $value
     *
     * @throws SessionIsLocked
     */
    public function flash(string $key, $value = true) :void;
    
    /**
     * Flash messages into the session. The data will be removed after saving the session once.
     *
     * @param  mixed  $value
     *
     * @throws SessionIsLocked
     */
    public function flashNow(string $key, $value) :void;
    
    /**
     * Flash user input into the session. The input will be removed after saving the session twice.
     *
     * @param  array<string,mixed>  $input
     *
     * @throws SessionIsLocked
     */
    public function flashInput(array $input) :void;
    
    /**
     * Keep flash messages for another save session cycle.
     *
     * @throws SessionIsLocked
     */
    public function reflash() :void;
    
    /**
     * Keep some flash messages for another save session cycle.
     *
     * @param  string|string[]  $keys
     *
     * @throws SessionIsLocked
     */
    public function keep($keys = null) :void;
    
    /**
     * Flush all developer provided data from the session.
     *
     * @throws SessionIsLocked
     */
    public function flush() :void;
    
    /**
     * @throws SessionIsLocked
     */
    public function remove(string $key) :void;
    
    /**
     * Get a value from the session and remove it.
     *
     * @return mixed
     * @throws SessionIsLocked
     */
    public function pull(string $key, $default = null);
    
    /**
     * @param  array|MessageBag  $provider
     *
     * @throws SessionIsLocked
     */
    public function withErrors($provider, string $bag = 'default') :void;
    
}