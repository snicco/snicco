<?php

declare(strict_types=1);

namespace Snicco\Auth\Controllers;

use Snicco\Http\Controller;
use Snicco\Http\Psr7\Request;
use Snicco\Http\Psr7\Response;

class WpLoginRedirectController extends Controller
{
    
    public function __invoke(Request $request) :Response
    {
        
        if ($request->input('action') === 'confirmation') {
            
            return $this->response_factory->null();
            
        }
        
        return $this->response_factory->redirectToLogin(
            $request->boolean('reauth'),
            $request->query('redirect_to', ''),
            301
        );
        
    }
    
}