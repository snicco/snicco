<?php

declare(strict_types=1);

namespace Snicco\Session\Events;

use WP_User;
use Snicco\EventDispatcher\Events\CoreEvent;
use Snicco\EventDispatcher\Contracts\MappedAction;

class NewLogin extends CoreEvent implements MappedAction
{
    
    public string  $user_login;
    public WP_User $user;
    
    public function __construct(string $user_login, WP_User $user)
    {
        $this->user_login = $user_login;
        $this->user = $user;
    }
    
    public function shouldDispatch() :bool
    {
        return true;
    }
    
}