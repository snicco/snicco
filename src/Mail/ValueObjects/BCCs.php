<?php

declare(strict_types=1);

namespace Snicco\Mail\ValueObjects;

/**
 * @api
 */
final class BCCs extends Collection
{
    
    public function __construct(BCC ...$names)
    {
        parent::__construct(...$names);
    }
    
}