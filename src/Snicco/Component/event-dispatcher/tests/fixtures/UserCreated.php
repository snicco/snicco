<?php

declare(strict_types=1);

namespace Snicco\Component\EventDispatcher\Tests\fixtures;

use Snicco\Component\EventDispatcher\ClassAsPayload;
use Snicco\Component\EventDispatcher\Contracts\Event;

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