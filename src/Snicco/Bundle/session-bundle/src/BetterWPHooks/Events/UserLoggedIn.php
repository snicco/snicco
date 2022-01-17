<?php

declare(strict_types=1);

namespace Snicco\SessionBundle\BetterWPHooks\Events;

use WP_User;
use Snicco\EventDispatcher\Contracts\MappedAction;
use Snicco\Component\Core\EventDispatcher\Events\CoreEvent;

class UserLoggedIn extends CoreEvent implements MappedAction
{
    
    /**
     * @var string
     */
    public $user_login;
    
    /**
     * @var WP_User
     */
    public $user;
    
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