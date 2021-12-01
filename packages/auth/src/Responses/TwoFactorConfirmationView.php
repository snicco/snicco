<?php

declare(strict_types=1);

namespace Snicco\Auth\Responses;

use Snicco\View\ViewEngine;
use Snicco\Http\Psr7\Request;
use Snicco\View\Contracts\ViewInterface;
use Snicco\Auth\Contracts\Abstract2FAuthConfirmationView;

class TwoFactorConfirmationView extends Abstract2FAuthConfirmationView
{
    
    private ViewEngine $view_factory;
    
    public function __construct(ViewEngine $view_factory)
    {
        $this->view_factory = $view_factory;
    }
    
    public function toView(Request $request) :ViewInterface
    {
        return $this->view_factory->make('framework.auth.two-factor-challenge')->with([
            'post_to' => $request->path(),
        ]);
    }
    
}