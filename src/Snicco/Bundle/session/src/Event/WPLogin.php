<?php

declare(strict_types=1);

namespace Snicco\Bundle\Session\Event;

use Snicco\Component\BetterWPHooks\EventMapping\MappedHook;
use WP_User;

/**
 * @psalm-internal Snicco\Bundle\Session
 * @psalm-immutable
 */
final class WPLogin implements MappedHook
{
    public string $user_login;

    public WP_User $user;

    public function __construct(string $user_login, WP_User $user)
    {
        $this->user_login = $user_login;
        $this->user = $user;
    }

    public function shouldDispatch(): bool
    {
        return true;
    }

    public function name(): string
    {
        return self::class;
    }

    public function payload(): self
    {
        return $this;
    }
}
