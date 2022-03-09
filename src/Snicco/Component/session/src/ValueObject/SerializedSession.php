<?php

declare(strict_types=1);

namespace Snicco\Component\Session\ValueObject;

use InvalidArgumentException;

use function is_int;
use function is_string;

final class SerializedSession
{
    private string $data;

    private int $last_activity;

    private string $hashed_validator;

    /**
     * @var int|string|null
     */
    private $user_id;

    /**
     * @param int|string|null $user_id
     */
    private function __construct(string $data, string $hashed_validator, int $last_activity, $user_id = null)
    {
        /** @psalm-suppress DocblockTypeContradiction */
        if (! is_string($user_id) && ! is_int($user_id) && null !== $user_id) {
            throw new InvalidArgumentException('$user_id must be null, string or integer.');
        }

        $this->data = $data;
        $this->hashed_validator = $hashed_validator;
        $this->last_activity = $last_activity;
        $this->user_id = $user_id;
    }

    /**
     * @param int|string|null $user_id
     */
    public static function fromString(
        string $string,
        string $hashed_validator,
        int $last_activity_as_timestamp,
        $user_id = null
    ): SerializedSession {
        return new self($string, $hashed_validator, $last_activity_as_timestamp, $user_id);
    }

    public function lastActivity(): int
    {
        return $this->last_activity;
    }

    public function data(): string
    {
        return $this->data;
    }

    public function hashedValidator(): string
    {
        return $this->hashed_validator;
    }

    /**
     * @return int|string|null
     */
    public function userId()
    {
        return $this->user_id;
    }
}
