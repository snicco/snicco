<?php

declare(strict_types=1);

namespace Snicco\Auth\Contracts;

use Snicco\Http\Psr7\Request;
use Snicco\Http\ResponseFactory;

interface AuthConfirmation
{
    
    public function confirm(Request $request) :bool;
    
    /**
     * Return anything that can be converted to a response object.
     *
     * @see ResponseFactory::toResponse()
     */
    public function viewResponse(Request $request);
    
}