<?php

declare(strict_types=1);

namespace Snicco\Auth\Controllers;

use Snicco\Http\Controller;
use Snicco\Http\Psr7\Request;
use Snicco\Http\Psr7\Response;

class WPLoginRedirectController extends Controller
{
    
    public function __invoke(Request $request) :Response
    {
        // We don't want to handle the personal privacy deletion request.
        if ($request->input('action') === 'confirmation') {
            return $this->response_factory->delegateToWP();
        }
        
        return $this->response_factory->redirectToLogin(
            $request->boolean('reauth'),
            $request->query('redirect_to', ''),
            301
        );
    }
    
}