<?php

declare(strict_types=1);

namespace Snicco\Mail\ValueObjects;

/**
 * @api
 */
final class Recipients extends Collection
{
    
    public function __construct(Recipient ...$recipient)
    {
        parent::__construct(...$recipient);
    }
    
}