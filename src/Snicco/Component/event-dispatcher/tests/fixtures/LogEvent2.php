<?php

declare(strict_types=1);

namespace Snicco\Component\EventDispatcher\Tests\fixtures;

use Snicco\Component\EventDispatcher\ClassAsName;
use Snicco\Component\EventDispatcher\Tests\fixtures;
use Snicco\Component\EventDispatcher\ClassAsPayload;

class LogEvent2 implements fixtures\LoggableEvent
{
    
    use ClassAsName;
    use ClassAsPayload;
    
    private $message;
    
    public function __construct($message)
    {
        $this->message = $message;
    }
    
    public function message()
    {
        return $this->message;
    }
    
}