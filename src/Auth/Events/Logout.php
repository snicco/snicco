<?php

declare(strict_types=1);

namespace Snicco\Auth\Events;

use Snicco\Events\Event;
use Snicco\Session\Session;
use BetterWpHooks\Traits\IsAction;

class Logout extends Event
{
    
    use IsAction;
    
    public Session $session;
    public int     $user_id;
    
    public function __construct(Session $session, int $user_id)
    {
        $this->session = $session;
        $this->user_id = $user_id;
        
        /**
         * Fires after a user is logged out.
         *
         * @param  int  $user_id  ID of the user that was logged out.
         *
         * @since 5.5.0 Added the `$user_id` parameter.
         * @since 1.5.0
         */
        do_action('wp_logout', $this->session->userId());
    }
    
}