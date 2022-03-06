<?php

declare(strict_types=1);

namespace Snicco\Component\Session;

use Closure;
use Snicco\Component\Session\Exception\SessionIsLocked;
use Snicco\Component\Session\Exception\SessionWasAlreadyInvalidated;
use Snicco\Component\Session\Exception\SessionWasAlreadyRotated;

interface MutableSession
{

    /**
     * Store a user id in the session. The user id will be flushed when calling invalidate()
     *
     * @param string|int $user_id
     */
    public function setUserId($user_id): void;

    /**
     * Invalidate the session entirely. All data will be flushed.
     *
     * @throws SessionIsLocked
     * @throws SessionWasAlreadyInvalidated
     */
    public function invalidate(): void;

    /**
     * Invalidates the session id and keeps the current data.
     *
     * @throws SessionIsLocked
     * @throws SessionWasAlreadyRotated
     */
    public function rotate(): void;

    /**
     * @param string|string[] $keys
     *
     * @throws SessionIsLocked
     */
    public function forget($keys): void;

    /**
     * @param array<string,mixed>|string $key
     * @param mixed $value Only used if key is string
     *
     * @throws SessionIsLocked
     */
    public function put($key, $value = null): void;

    /**
     * @param array<string,mixed> $attributes
     *
     * @throws SessionIsLocked
     */
    public function replace(array $attributes): void;

    /**
     * @throws SessionIsLocked
     */
    public function putIfMissing(string $key, Closure $callback): void;

    /**
     * @throws SessionIsLocked
     */
    public function decrement(string $key, int $amount = 1): void;

    /**
     * @throws SessionIsLocked
     */
    public function increment(string $key, int $amount = 1, int $start_value = 0): void;

    /**
     * @param mixed $value
     *
     * @throws SessionIsLocked
     */
    public function push(string $key, $value): void;

    /**
     * Flash messages into the session. The data will be removed after saving the session twice.
     *
     * @param mixed $value
     *
     * @throws SessionIsLocked
     */
    public function flash(string $key, $value = true): void;

    /**
     * Flash messages into the session. The data will be removed after saving the session once.
     *
     * @param mixed $value
     *
     * @throws SessionIsLocked
     */
    public function flashNow(string $key, $value): void;

    /**
     * Flash user input into the session. The input will be removed after saving the session twice.
     *
     * @param array<string,mixed> $input
     *
     * @throws SessionIsLocked
     */
    public function flashInput(array $input): void;

    /**
     * Keep flash messages for another save session cycle.
     *
     * @throws SessionIsLocked
     */
    public function reflash(): void;

    /**
     * Keep some flash messages for another save session cycle.
     *
     * @param string|string[] $keys
     *
     * @throws SessionIsLocked
     */
    public function keep($keys): void;

    /**
     * Flush all developer provided data from the session.
     *
     * @throws SessionIsLocked
     */
    public function flush(): void;

    /**
     * @throws SessionIsLocked
     */
    public function remove(string $key): void;

}