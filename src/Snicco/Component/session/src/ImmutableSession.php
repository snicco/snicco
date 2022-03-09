<?php

declare(strict_types=1);

namespace Snicco\Component\Session;

use Snicco\Component\Session\ValueObject\SessionId;

interface ImmutableSession
{
    public function id(): SessionId;

    /**
     * Indicates if this session was newly created and has not been persisted yet.
     */
    public function isNew(): bool;

    /**
     * @return int|string|null return null if no user id has been set
     */
    public function userId();

    /**
     * @return int UNIX timestamp
     */
    public function createdAt(): int;

    /**
     * @return int UNIX timestamp
     */
    public function lastRotation(): int;

    /**
     * @return int UNIX timestamp
     */
    public function lastActivity(): int;

    /**
     * Checks if the given key is in the session and that the value is not NULL.
     */
    public function has(string $key): bool;

    /**
     * Check if the value for the key is truthy. {@see filter_var()}.
     */
    public function boolean(string $key, bool $default = false): bool;

    /**
     * Get the previous input of a user, typically during a form submission.
     *
     * @param mixed $default
     *
     * @return mixed
     */
    public function oldInput(string $key = null, $default = null);

    public function hasOldInput(string $key = null): bool;

    /**
     * Return all USER-PROVIDED entries in the session.
     */
    public function all(): array;

    /**
     * @param string|string[] $keys
     */
    public function only($keys): array;

    /**
     * Returns true if all the given keys are not in the session.
     *
     * @param string|string[] $keys
     */
    public function missing($keys): bool;

    /**
     * Returns true if all the given keys are in the session.
     *
     * @param string|string[] $keys
     */
    public function exists($keys): bool;

    /**
     * Get a value form the session with dot notation.
     * $session->get('user.name', 'calvin').
     *
     * @param mixed $default
     *
     * @return mixed
     */
    public function get(string $key, $default = null);
}
