<?php

declare(strict_types=1);

namespace Snicco\Component\Psr7ErrorHandler\Log;

use Psr\Http\Message\RequestInterface;

/**
 * @api
 */
interface RequestContext
{
    
    public function add(array $context, RequestInterface $request) :array;
    
}