<?php

declare(strict_types=1);

namespace Snicco\Core\Controllers;

use Snicco\Core\Http\Controller;
use Snicco\Core\Http\Responses\DelegatedResponse;

class FallBackController extends Controller
{
    
    public function delegate() :DelegatedResponse
    {
        return $this->response_factory->delegateToWP();
    }
    
}