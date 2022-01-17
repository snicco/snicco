<?php

declare(strict_types=1);

namespace Snicco\SignedUrl\Contracts;

use Snicco\SignedUrl\Secret;

abstract class Hasher
{
    
    /**
     * @var Secret
     */
    protected $secret;
    
    public function __construct(Secret $secret)
    {
        $this->secret = $secret;
    }
    
    /**
     * Hash a plain_text and return a BINARY presentation.
     */
    abstract public function hash(string $plain_text) :string;
    
}