<?php

declare(strict_types=1);

namespace Snicco\Component\Psr7ErrorHandler;

use Throwable;

final class IdentifiedThrowable
{
    
    private Throwable $throwable;
    private string    $identifier;
    
    public function __construct(Throwable $throwable, string $identifier)
    {
        $this->throwable = $throwable;
        $this->identifier = $identifier;
    }
    
    public function throwable() :Throwable
    {
        return $this->throwable;
    }
    
    public function identifier() :string
    {
        return $this->identifier;
    }
    
}