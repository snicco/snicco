<?php

declare(strict_types=1);

namespace Snicco\Mail\ValueObjects;

/**
 * @api
 */
final class CCs extends Collection
{
    
    public function __construct(CC ...$names)
    {
        parent::__construct(...$names);
    }
    
}