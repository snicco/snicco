<?php

declare(strict_types=1);

namespace Snicco\Component\Session\Event;

use Snicco\Component\Session\ImmutableSession;

/**
 * @api
 */
final class SessionRotated
{

    public ImmutableSession $session;

    public function __construct(ImmutableSession $session)
    {
        $this->session = $session;
    }

}