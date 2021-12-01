<?php

namespace Snicco\Http\Responses;

use Snicco\Http\Psr7\Response;

/**
 * This response class can be returned to indicate the output of a response
 * should be delegated to WordPress. Any added headers will still be added.
 */
class DelegatedResponse extends Response
{
    
}