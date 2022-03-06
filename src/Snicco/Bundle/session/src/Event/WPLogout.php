<?php

declare(strict_types=1);


namespace Snicco\Bundle\Session\Event;

use Snicco\Component\BetterWPHooks\EventMapping\MappedHook;


/**
 * @psalm-internal Snicco\Bundle\Session
 * @psalm-immutable
 */
final class WPLogout implements MappedHook
{
    public int $user_id;

    public function __construct(int $user_id)
    {
        $this->user_id = $user_id;
    }

    public function shouldDispatch(): bool
    {
        return true;
    }

    public function name(): string
    {
        return self::class;
    }

    public function payload()
    {
        return $this;
    }
}