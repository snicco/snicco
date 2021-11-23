<?php

declare(strict_types=1);

namespace Tests\integration\EventDispatcher\fixtures;

use Snicco\EventDispatcher\ClassAsName;
use Snicco\EventDispatcher\ClassAsPayload;
use Tests\integration\EventDispatcher\fixtures;

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