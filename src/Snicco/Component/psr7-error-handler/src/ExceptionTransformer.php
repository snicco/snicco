<?php

declare(strict_types=1);

namespace Snicco\Component\Psr7ErrorHandler;

use Throwable;

/**
 * @api
 */
interface ExceptionTransformer
{
    
    public function transform(Throwable $e) :Throwable;
    
}