<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPHooks\Tests\fixtures;

use Snicco\Component\BetterWPHooks\EventMapping\ExposeToWP;

final class PlainObjectEvent implements ExposeToWP
{
    
    public string $value;
    
    public function __construct(string $value)
    {
        $this->value = $value;
    }
    
}