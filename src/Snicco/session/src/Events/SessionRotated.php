<?php

declare(strict_types=1);

namespace Snicco\Session\Events;

use Snicco\Session\Contracts\ImmutableSessionInterface;

final class SessionRotated
{
    
    /**
     * @var ImmutableSessionInterface
     */
    public $session;
    
    public function __construct(ImmutableSessionInterface $session)
    {
        $this->session = $session;
    }
    
}