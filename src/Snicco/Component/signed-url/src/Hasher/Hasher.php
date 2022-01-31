<?php

declare(strict_types=1);

namespace Snicco\Component\SignedUrl\Hasher;

use Snicco\Component\SignedUrl\Secret;

abstract class Hasher
{
    
    protected Secret $secret;
    
    public function __construct(Secret $secret)
    {
        $this->secret = $secret;
    }
    
    /**
     * Hash a plain_text and return a BINARY presentation.
     */
    abstract public function hash(string $plain_text) :string;
    
}