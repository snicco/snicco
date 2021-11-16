<?php

declare(strict_types=1);

namespace Snicco\Controllers;

use Snicco\Http\Controller;
use Snicco\Http\Psr7\Response;

class ViewController extends Controller
{
    
    public function handle(...$args) :Response
    {
        [$view, $data, $status, $headers] = array_slice($args, -4);
        
        return $this->response_factory->view($view, $data, $status, $headers);
    }
    
}