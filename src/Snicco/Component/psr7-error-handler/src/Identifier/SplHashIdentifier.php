<?php

declare(strict_types=1);

namespace Snicco\Component\Psr7ErrorHandler\Identifier;

use Throwable;
use Snicco\Component\Psr7ErrorHandler\Identifier;

use function spl_object_hash;

final class SplHashIdentifier implements Identifier
{
    
    public function identify(Throwable $e) :string
    {
        return spl_object_hash($e);
    }
    
}