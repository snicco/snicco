<?php

declare(strict_types=1);

namespace Snicco\EventDispatcher;

use Snicco\EventDispatcher\Contracts\Event;

final class WildCardEvent implements Event
{
    
    private string $event_name;
    private array  $payload;
    
    public function __construct(string $event_name, array $payload)
    {
        $this->event_name = $event_name;
        $this->payload = $payload;
    }
    
    public function name() :string
    {
        return $this->event_name;
    }
    
    public function data() :array
    {
        return $this->payload;
    }
    
}