<?php

namespace Snicco\Core\Http\Responses;

use Snicco\Core\Http\Psr7\Response;

/**
 * This response class can be returned to indicate the output of a response
 * should be delegated to WordPress. Any added headers will still be added.
 */
final class DelegatedResponse extends Response
{
    
}