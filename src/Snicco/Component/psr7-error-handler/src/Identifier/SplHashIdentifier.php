<?php

declare(strict_types=1);

namespace Snicco\Component\Psr7ErrorHandler\Identifier;

use Throwable;
use Snicco\Component\Psr7ErrorHandler\ExceptionIdentifier;

use function spl_object_hash;

/**
 * @api
 */
final class SplHashIdentifier implements ExceptionIdentifier
{
    
    public function identify(Throwable $e) :string
    {
        return spl_object_hash($e);
    }
    
}