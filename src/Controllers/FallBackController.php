<?php

declare(strict_types=1);

namespace Snicco\Controllers;

use Snicco\Http\Controller;
use Snicco\Http\Responses\DelegatedResponse;

class FallBackController extends Controller
{
    
    public function delegate() :DelegatedResponse
    {
        return $this->response_factory->delegateToWP();
    }
    
}