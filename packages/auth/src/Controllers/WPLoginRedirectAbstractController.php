<?php

declare(strict_types=1);

namespace Snicco\Auth\Controllers;

use Snicco\HttpRouting\Http\Psr7\Request;
use Snicco\HttpRouting\Http\Psr7\Response;
use Snicco\HttpRouting\Http\AbstractController;

class WPLoginRedirectAbstractController extends AbstractController
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