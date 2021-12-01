<?php

declare(strict_types=1);

namespace Tests\Codeception\shared\TestDependencies;

class Baz
{
    
    public string $baz = 'baz';
    
    public function __toString()
    {
        return $this->baz;
    }
    
}