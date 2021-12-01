<?php

declare(strict_types=1);

namespace Tests\BetterWPHooks\fixtures;

use Tests\BetterWPHooks\fixtures;
use Snicco\EventDispatcher\ClassAsName;
use Snicco\EventDispatcher\ClassAsPayload;

class LogEvent1 implements fixtures\LoggableEvent
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