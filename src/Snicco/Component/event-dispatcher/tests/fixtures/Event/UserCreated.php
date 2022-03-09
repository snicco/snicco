<?php

declare(strict_types=1);

namespace Snicco\Component\EventDispatcher\Tests\fixtures\Event;

use Snicco\Component\EventDispatcher\ClassAsPayload;
use Snicco\Component\EventDispatcher\Event;

class UserCreated implements Event
{
    use ClassAsPayload;

    public $user_name;

    public function __construct($user_name)
    {
        $this->user_name = $user_name;
    }

    public function name(): string
    {
        return 'my_plugin_user_created';
    }
}
