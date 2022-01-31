<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPHooks\Tests\fixtures;

use Snicco\Component\EventDispatcher\Event;
use Snicco\Component\BetterWPHooks\EventMapping\ExposeToWP;

use function get_class;

final class CustomNameEvent implements ExposeToWP, Event
{
    
    public string   $value;
    private ?string $name;
    
    public function __construct(string $value, string $name = null)
    {
        $this->value = $value;
        $this->name = $name;
    }
    
    public function name() :string
    {
        return $this->name ? : get_class($this);
    }
    
    public function payload()
    {
        return $this;
    }
    
}