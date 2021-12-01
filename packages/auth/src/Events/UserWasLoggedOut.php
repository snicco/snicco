<?php

declare(strict_types=1);

namespace Snicco\Auth\Events;

use Snicco\EventDispatcher\Events\CoreEvent;

class UserWasLoggedOut extends CoreEvent
{
    
    public int $user_id;
    
    public function __construct(int $user_id)
    {
        $this->user_id = $user_id;
        
        /**
         * Fires after a user is logged out.
         *
         * @param  int  $user_id  ID of the user that was logged out.
         *
         * @since 5.5.0 Added the `$user_id` parameter.
         * @since 1.5.0
         */
        do_action('wp_logout', $user_id);
    }
    
}