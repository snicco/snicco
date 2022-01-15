<?php

declare(strict_types=1);

namespace Snicco\Auth\Responses;

use Snicco\View\ViewEngine;
use Snicco\View\Contracts\ViewInterface;
use Snicco\HttpRouting\Http\Psr7\Request;
use Snicco\Auth\Contracts\Abstract2FAuthConfirmationView;

class TwoFactorConfirmationView extends Abstract2FAuthConfirmationView
{
    
    private ViewEngine $view_engine;
    
    public function __construct(ViewEngine $view_engine)
    {
        $this->view_engine = $view_engine;
    }
    
    public function toView(Request $request) :ViewInterface
    {
        return $this->view_engine->make('framework.auth.two-factor-challenge')->with([
            'post_to' => $request->path(),
        ]);
    }
    
}