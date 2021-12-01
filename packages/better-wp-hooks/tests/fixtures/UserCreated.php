<?php

declare(strict_types=1);

namespace Tests\BetterWPHooks\fixtures;

use Snicco\EventDispatcher\ClassAsPayload;
use Snicco\EventDispatcher\Contracts\Event;

class UserCreated implements Event
{
    
    use ClassAsPayload;
    
    public $user_name;
    
    public function __construct($user_name)
    {
        $this->user_name = $user_name;
    }
    
    public function getName() :string
    {
        return 'my_plugin_user_created';
    }
    
}