<?php

declare(strict_types=1);

namespace Snicco\Auth\Controllers;

use Snicco\Core\Http\Controller;
use Snicco\Core\Http\Psr7\Request;
use Snicco\Core\Http\Psr7\Response;

class WPLoginRedirectController extends Controller
{
    
    public function __invoke(Request $request) :Response
    {
        // We don't want to handle the personal privacy deletion request.
        if ($request->input('action') === 'confirmation') {
            return $this->response_factory->delegateToWP();
        }
        
        return $this->response_factory->redirect()->toLogin(
            $request->query('redirect_to', ''),
            $request->boolean('reauth'),
            301
        );
    }
    
}