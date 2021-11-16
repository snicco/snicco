<?php

declare(strict_types=1);

namespace Tests\fixtures\TestDependencies;

class Bar
{
    
    public string $bar = 'bar';
    
    public function __toString()
    {
        return $this->bar;
    }
    
}