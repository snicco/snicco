<?php

declare(strict_types=1);

namespace Snicco\Core\Controllers;

use Snicco\Core\Http\Controller;
use Snicco\Core\Http\Psr7\Request;
use Snicco\Core\Http\Responses\RedirectResponse;

class RedirectController extends Controller
{
    
    public function to(...$args) :RedirectResponse
    {
        [$location, $status_code, $secure, $absolute] = array_slice($args, -4);
        
        return $this->response_factory->redirect()
                                      ->to($location, $status_code, [], $secure, $absolute);
    }
    
    public function away(...$args) :RedirectResponse
    {
        [$location, $status_code] = array_slice($args, -2);
        
        return $this->response_factory->redirect()
                                      ->away($location, $status_code);
    }
    
    public function toRoute(...$args) :RedirectResponse
    {
        [$route, $status_code, $params] = array_slice($args, -3);
        
        return $this->response_factory->redirect()
                                      ->toRoute($route, $status_code, $params);
    }
    
    /** @todo tests */
    public function exit(Request $request)
    {
        return $this->response_factory
            ->view('framework.redirect-protection', [
                'untrusted_url' => $request->query('intended_redirect'),
                'home_url' => $this->url->toRoute('home'),
            ]);
    }
    
}